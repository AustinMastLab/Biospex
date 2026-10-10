<?php

/*
 * Copyright (C) 2014 - 2025, Biospex
 * biospex@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services\DarwinCore;

use App\Models\Subject;
use App\Services\Helpers\GeneralService;
use Illuminate\Support\Collection;

/**
 * Darwin Core Validation Service
 *
 * Extracted and enhanced validation logic for batch processing of Darwin Core imports.
 * Handles identifier validation, duplicate detection, and data sanitization.
 */
class DwcValidationService
{
    /**
     * Valid identifier column names from config
     */
    private array $identifiers;

    /**
     * Constructor - inject configuration values and services
     */
    public function __construct(private GeneralService $generalService)
    {
        $this->identifiers = config('config.dwcRequiredFields.extension.identifier');
    }

    /**
     * Validate if identifier is a proper URN UUID or other acceptable format.
     */
    public function isValidIdentifier(string $value): bool
    {
        // If it starts with 'urn:uuid:', validate the full format
        if (str_starts_with(strtolower($value), 'urn:uuid:')) {
            return $this->isValidUrnUuid($value);
        }

        // If it's just a UUID without URN prefix, that's also valid
        if ($this->isValidUuid($value)) {
            return true;
        }

        // Accept other identifier formats that don't contain 'http'
        // and aren't malformed URN attempts
        if (str_starts_with(strtolower($value), 'urn:') && ! str_starts_with(strtolower($value), 'urn:uuid:')) {
            // It's some other URN format, which might be valid
            return true;
        }

        // For non-URN identifiers, accept them as long as they don't contain urn:
        return ! str_contains(strtolower($value), 'urn:');
    }

    /**
     * Validate URN UUID format.
     */
    public function isValidUrnUuid(string $value): bool
    {
        $pattern = '/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate UUID format (without URN prefix).
     */
    public function isValidUuid(string $value): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

        return preg_match($pattern, $value) === 1;
    }

    /**
     * If identifier is a uuid, strip the namespace. Otherwise return value.
     */
    public function checkIdentifierUuid($value): mixed
    {
        // Handle URN UUID format first
        if (str_starts_with(strtolower($value), 'urn:uuid:')) {
            // Remove 'urn:uuid:' prefix
            return substr($value, 9);
        }

        // Original logic for other UUID formats
        $pattern = '/\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}?$/i';

        return preg_match($pattern, $value, $matches) ? $matches[0] : $value;
    }

    /**
     * Get identifier value from row data using meta fields.
     */
    public function getIdentifierValue(array $row, array $metaFields, array $header): mixed
    {
        $identifierColumnValues = collect($metaFields)
            ->intersect($this->identifiers)
            ->filter(function ($identifier, $key) use ($row, $header) {
                if (isset($row[$header[$key]])
                    && ! empty($row[$header[$key]])
                    && (! str_contains($row[$header[$key]], 'http'))
                    && $this->isValidIdentifier($row[$header[$key]])) {
                    return true;
                }

                return false;
            })->map(function ($identifier, $key) use ($row, $header) {
                return $row[$header[$key]];
            });

        if ($identifierColumnValues->isEmpty()) {
            return false;
        }

        return $identifierColumnValues->first();
    }

    /**
     * Check for identifier column in meta fields.
     */
    public function checkForIdentifierColumn(array $metaFields): bool
    {
        $identifiers = collect($metaFields)->intersect($this->identifiers);

        return $identifiers->isNotEmpty();
    }

    /**
     * Validate a batch of media rows.
     * Returns array with 'valid', 'rejected' and 'duplicates' keys.
     */
    public function validateBatch(array $batch, array $header, array $metaFields, int $projectId): array
    {
        $valid = [];
        $rejected = [];
        $duplicates = [];
        $seenImageIds = [];

        foreach ($batch as $row) {
            $validationResult = $this->validateMediaRow($row, $header, $metaFields);

            if ($validationResult['valid']) {
                $imageId = $validationResult['imageId'];

                // Check for duplicates within batch
                if (isset($seenImageIds[$imageId])) {
                    $duplicates[] = ['Reason' => t('Duplicate imageId within batch.')] + $row;

                    continue;
                }

                $seenImageIds[$imageId] = true;
                $valid[] = $validationResult['data'];
            } else {
                $rejected[] = $validationResult['rejection'];
            }
        }

        // Check for database duplicates in batch
        if (! empty($valid)) {
            $imageIds = collect($valid)->pluck('imageId')->toArray();
            $dbDuplicates = $this->checkDatabaseDuplicates($imageIds, $projectId);

            if (! empty($dbDuplicates)) {
                // Filter out database duplicates
                $validFiltered = [];
                foreach ($valid as $item) {
                    if (in_array($item['imageId'], $dbDuplicates)) {
                        $duplicates[] = ['Reason' => t('Duplicate imageId in database.')] + $item;
                    } else {
                        $validFiltered[] = $item;
                    }
                }
                $valid = $validFiltered;
            }
        }

        return [
            'valid' => $valid,
            'rejected' => $rejected,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * Validate a single media row.
     */
    public function validateMediaRow(array $row, array $header, array $metaFields): array
    {
        // Sanitize UTF-8 data
        $row = $this->sanitizeRowData($row);

        // Extract valid identifier
        $identifier = $this->extractValidIdentifier($row, $header, $metaFields);

        if (! $identifier) {
            return [
                'valid' => false,
                'rejection' => ['Reason' => t('All identifier columns empty, identifier is URL, or invalid URN format.')] + $row,
            ];
        }

        // Set imageId
        $row['imageId'] = $this->checkIdentifierUuid($identifier);

        // Check required columns
        if (! trim($row['imageId'])) {
            return [
                'valid' => false,
                'rejection' => ['Reason' => t('Missing required imageId value.')] + $row,
            ];
        }

        if (empty($row['accessURI'])) {
            return [
                'valid' => false,
                'rejection' => ['Reason' => t('Missing accessURI.')] + $row,
            ];
        }

        return [
            'valid' => true,
            'data' => $row,
            'imageId' => $row['imageId'],
        ];
    }

    /**
     * Extract valid identifier from row data.
     */
    public function extractValidIdentifier(array $row, array $header, array $metaFields): ?string
    {
        return $this->getIdentifierValue($row, $metaFields, $header);
    }

    /**
     * Check for database duplicates by imageId and projectId.
     * Returns array of duplicate imageIds.
     */
    public function checkDatabaseDuplicates(array $imageIds, int $projectId): array
    {
        if (empty($imageIds)) {
            return [];
        }

        // Query subjects collection for existing imageIds in this project
        $existingSubjects = Subject::where('project_id', $projectId)
            ->whereIn('imageId', $imageIds)
            ->pluck('imageId')
            ->toArray();

        return $existingSubjects;
    }

    /**
     * Sanitize row data for UTF-8 encoding and clean up.
     */
    private function sanitizeRowData(array $row): array
    {
        $sanitized = [];

        foreach ($row as $key => $value) {
            if (is_string($value)) {
                // Fix UTF-8 encoding issues using GeneralService
                $value = $this->generalService->fixUtf8($value);

                // Replace problematic characters with safe alternatives
                $replacements = [
                    "\xC2\xB0" => '°',  // Degree symbol
                    "\xEF\xBF\xBD" => '', // Replacement character (invalid UTF-8)
                    "\x80" => '', // Invalid UTF-8 byte
                    "\x81" => '', // Invalid UTF-8 byte
                    "\x8D" => '', // Invalid UTF-8 byte
                    "\x8F" => '', // Invalid UTF-8 byte
                    "\x90" => '', // Invalid UTF-8 byte
                    "\x9D" => '', // Invalid UTF-8 byte
                    chr(194).chr(176) => '°', // Another degree symbol representation
                ];

                $value = str_replace(array_keys($replacements), array_values($replacements), $value);

                // Remove any remaining non-printable or problematic UTF-8 characters
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

                // Remove high-bit characters that might be invalid UTF-8
                $value = preg_replace('/[\x80-\xFF]/', '', $value);

                // Final UTF-8 validation and cleanup
                if (! mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }

                // Clean up whitespace
                $value = trim($value);
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * Sanitize occurrence data for UTF-8 encoding issues.
     */
    public function sanitizeOccurrenceData(array $occurrenceData): array
    {
        $sanitized = [];

        foreach ($occurrenceData as $occurrenceId => $occurrence) {
            $sanitized[$occurrenceId] = $this->sanitizeRowData($occurrence);
        }

        return $sanitized;
    }
}
