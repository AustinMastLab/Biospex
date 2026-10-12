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

use App\Models\ImportOccurrence;
use App\Models\Subject;
use App\Services\Csv\Csv;
use App\Services\DarwinCore\Exceptions\MetaFileException;
use App\Services\DarwinCore\ValueObjects\ProcessedMetaData;
use App\Services\Project\HeaderService;
use Exception;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\WriteConcern;

/**
 * Darwin Core Batch Processor
 *
 * Memory-efficient batch processing engine for Darwin Core imports.
 * Handles single-pass processing with complete occurrence data embedding.
 */
class DwcBatchProcessor
{
    private const BATCH_SIZE = 5000;

    private array $duplicates = [];

    private array $rejectedMedia = [];

    private int $subjectCount = 0;

    private array $properties = [];

    private int $projectId;

    private ProcessedMetaData $processedMetaData;

    private string $importSessionId;

    public function __construct(
        private readonly MetaFileProcessor $metaFileProcessor,
        private readonly Csv $csv,
        private readonly DwcValidationService $validation,
        private readonly HeaderService $headerService
    ) {
        // Configuration values are now injected in DwcValidationService and MetaFileProcessor constructors
    }

    /**
     * Process Darwin Core Archive with batch processing.
     *
     * @throws \League\Csv\Exception|MetaFileException
     */
    public function processArchive(int $projectId, string $directory): array
    {
        $this->projectId = $projectId;
        $this->importSessionId = md5($projectId.microtime(true));

        try {
            // Parse meta.xml
            $metaFile = $directory.'/meta.xml';
            $this->processedMetaData = $this->metaFileProcessor->process($metaFile);

            // Save meta file to database
            $this->metaFileProcessor->saveMetaFile($projectId, $this->processedMetaData->xmlContent);

            $metaFields = $this->processedMetaData->metaFields;

            // Validate identifier columns exist
            if (! $this->validation->checkForIdentifierColumn($metaFields['extension'])) {
                throw new Exception('No identifier columns found in meta.xml extension fields');
            }

            // Load occurrence data into memory map (core is always occurrence data)
            $occurrenceData = $this->loadOccurrenceData($directory, $metaFields);

            // Process media file with validation and batch operations
            $this->processMediaWithValidation($directory, $metaFields, $occurrenceData, $projectId);

            // Clean up MongoDB collection if it was used
            if (is_string($occurrenceData)) {
                $this->clearMongoCollection();
            }

            return [
                'success' => true,
                'subjects_created' => $this->subjectCount,
                'duplicates_count' => count($this->duplicates),
                'rejected_count' => count($this->rejectedMedia),
            ];

        } catch (Exception $e) {
            Log::error('Darwin Core batch processing failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Load occurrence data using hybrid memory/MongoDB approach based on file size.
     *
     * @throws \League\Csv\Exception
     */
    protected function loadOccurrenceData(string $directory, array $metaFields): array|string
    {
        $occurrenceFile = $directory.'/'.$this->processedMetaData->getCoreFile();

        if (! file_exists($occurrenceFile)) {
            throw new Exception("Core occurrence file not found: {$occurrenceFile}. Darwin Core Archives must have a valid occurrence core file.");
        }

        // Check file size and determine processing method
        $fileSize = filesize($occurrenceFile);
        $useMongoDB = $this->shouldUseMongoDBProcessing($fileSize, $occurrenceFile);

        if ($useMongoDB) {
            return $this->loadOccurrenceDataToMongoDB($occurrenceFile);
        } else {
            return $this->loadOccurrenceDataToMemory($occurrenceFile);
        }
    }

    /**
     * Determine if MongoDB processing should be used based on file size and row count thresholds.
     */
    private function shouldUseMongoDBProcessing(int $fileSize, string $filePath): bool
    {
        $fileSizeThresholdMB = config('config.dwc_import_thresholds.file_size_mb', 30);
        $rowCountThreshold = config('config.dwc_import_thresholds.row_count', 25000);

        // Check file size first (fastest check)
        if ($fileSize > ($fileSizeThresholdMB * 1024 * 1024)) {
            return true;
        }

        // For borderline files, do a quick row count
        $estimatedRows = $this->estimateRowCount($filePath);
        if ($estimatedRows > $rowCountThreshold) {
            return true;
        }

        return false;
    }

    /**
     * Estimate row count by quickly scanning the file.
     */
    private function estimateRowCount(string $filePath): int
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return 0;
        }

        $rowCount = 0;
        while (! feof($handle)) {
            if (fgets($handle) !== false) {
                $rowCount++;
            }
        }
        fclose($handle);

        // Subtract 1 for header row
        return max(0, $rowCount - 1);
    }

    /**
     * Load occurrence data into memory map for fast lookup (original implementation).
     *
     * @throws \League\Csv\Exception
     */
    private function loadOccurrenceDataToMemory(string $occurrenceFile): array
    {

        $this->csv->readerCreateFromPath($occurrenceFile);
        $this->csv->setDelimiter($this->processedMetaData->getCoreDelimiter());
        $this->csv->setEnclosure($this->processedMetaData->getCoreEnclosure());
        $this->csv->setHeaderOffset(0);

        $header = $this->csv->getHeader();

        // Save occurrence headers
        $this->saveHeaderArray($header, 'occurrence');

        $records = $this->csv->getRecords();
        $occurrenceData = [];

        foreach ($records as $row) {
            if (empty($row) || count($row) !== count($header)) {
                continue;
            }

            // Use first column (ID) as key for lookup
            $occurrenceId = $row[$header[0]] ?? null;
            if ($occurrenceId) {
                $occurrenceRecord = array_combine($header, $row);
                // Sanitize occurrence data for UTF-8 issues
                $occurrenceData[$occurrenceId] = $this->validation->sanitizeOccurrenceData([$occurrenceId => $occurrenceRecord])[$occurrenceId];
            }
        }

        return $occurrenceData;
    }

    /**
     * Load occurrence data into MongoDB for streaming large files.
     *
     * @throws \League\Csv\Exception
     */
    private function loadOccurrenceDataToMongoDB(string $occurrenceFile): string
    {

        // Clear any existing data for this import session
        ImportOccurrence::clearImportSession($this->importSessionId);

        $this->csv->readerCreateFromPath($occurrenceFile);
        $this->csv->setDelimiter($this->processedMetaData->getCoreDelimiter());
        $this->csv->setEnclosure($this->processedMetaData->getCoreEnclosure());
        $this->csv->setHeaderOffset(0);

        $header = $this->csv->getHeader();

        // Save occurrence headers
        $this->saveHeaderArray($header, 'occurrence');

        $records = $this->csv->getRecords();
        $batchData = [];
        $count = 0;

        foreach ($records as $row) {
            if (empty($row) || count($row) !== count($header)) {
                continue;
            }

            // Use first column (ID) as key for lookup
            $occurrenceId = $row[$header[0]] ?? null;
            if ($occurrenceId) {
                $occurrenceRecord = array_combine($header, $row);
                // Sanitize occurrence data for UTF-8 issues
                $sanitizedData = $this->validation->sanitizeOccurrenceData([$occurrenceId => $occurrenceRecord])[$occurrenceId];

                $batchData[] = [
                    'occurrence_id' => $occurrenceId,
                    'data' => $sanitizedData,
                    'project_id' => $this->projectId,
                    'import_session_id' => $this->importSessionId,
                ];

                // Insert in batches of 1000 for performance
                if (count($batchData) >= 1000) {
                    ImportOccurrence::insert($batchData);
                    $count += count($batchData);
                    $batchData = [];

                }
            }
        }

        // Insert remaining records
        if (! empty($batchData)) {
            ImportOccurrence::insert($batchData);
            $count += count($batchData);
        }

        // Return import session ID to identify this dataset
        return $this->importSessionId;
    }

    /**
     * Clear MongoDB collection after processing.
     */
    private function clearMongoCollection(): void
    {
        if (isset($this->importSessionId)) {
            ImportOccurrence::clearImportSession($this->importSessionId);
        }
    }

    /**
     * Process media file with validation and batch operations.
     *
     * @throws \League\Csv\Exception
     */
    protected function processMediaWithValidation(
        string $directory,
        array $metaFields,
        array|string $occurrenceData,
        int $projectId
    ): void {
        $mediaFile = $directory.'/'.$this->processedMetaData->getExtensionFile();

        if (! file_exists($mediaFile)) {
            throw new Exception("Media file not found: {$mediaFile}");
        }

        $this->csv->readerCreateFromPath($mediaFile);
        $this->csv->setDelimiter($this->processedMetaData->getExtDelimiter());
        $this->csv->setEnclosure($this->processedMetaData->getExtEnclosure());
        $this->csv->setHeaderOffset(0);

        $header = $this->csv->getHeader();
        $records = $this->csv->getRecords();

        // Save header for property creation (extension file contains image/media data)
        $this->saveHeaderArray($header, 'image');

        $batch = [];
        $rowCount = 0;

        foreach ($records as $row) {
            if (empty($row) || count($row) !== count($header)) {
                continue;
            }

            $rowCount++;
            $rowData = array_combine($header, $row);
            $batch[] = $rowData;

            // Process batch when it reaches the batch size
            if (count($batch) >= self::BATCH_SIZE) {
                $this->processBatch($batch, $projectId, $metaFields['extension'], $header, $occurrenceData);
                $batch = [];

                // Memory cleanup
                if ($rowCount % (self::BATCH_SIZE * 5) === 0) {
                    gc_collect_cycles();
                }
            }
        }

        // Process remaining items in final batch
        if (! empty($batch)) {
            $this->processBatch($batch, $projectId, $metaFields['extension'], $header, $occurrenceData);
        }

    }

    /**
     * Process a batch of media rows with optimized batch operations.
     */
    protected function processBatch(
        array $batch,
        int $projectId,
        array $metaFields,
        array $header,
        array|string $occurrenceData
    ): void {
        // Validate the entire batch
        $validationResult = $this->validation->validateBatch($batch, $header, $metaFields, $projectId);

        // Collect rejected and duplicate rows for the import reports
        $this->rejectedMedia = array_merge($this->rejectedMedia, $validationResult['rejected']);
        $this->duplicates = array_merge($this->duplicates, $validationResult['duplicates']);

        if (empty($validationResult['valid'])) {
            return;
        }

        // For MongoDB-based occurrence data, collect all occurrence IDs and batch lookup
        $batchedOccurrences = [];
        if (is_string($occurrenceData)) {
            $occurrenceIds = [];
            foreach ($validationResult['valid'] as $row) {
                $occurrenceId = $row[$header[0]] ?? null;
                if ($occurrenceId) {
                    $occurrenceIds[] = $occurrenceId;
                }
            }
            $batchedOccurrences = $this->batchLookupOccurrences($occurrenceIds);
        }

        // Build subjects for valid records
        $subjects = [];
        foreach ($validationResult['valid'] as $row) {
            $subject = $this->buildSubject($row, $header, $projectId, $occurrenceData, $batchedOccurrences);
            if ($subject) {
                $subjects[] = $subject;
            }
        }

        if (! empty($subjects)) {
            // Bulk insert subjects (subjectCount is updated within the method)
            $this->bulkInsertSubjects($subjects);

        }
    }

    /**
     * Build a complete subject with embedded occurrence data.
     */
    protected function buildSubject(
        array $row,
        array $header,
        int $projectId,
        array|string $occurrenceData,
        array $batchedOccurrences = []
    ): ?array {
        try {
            // Get occurrence ID for lookup (first column in media file points to occurrence ID)
            $occurrenceId = $row[$header[0]] ?? null;

            // Base subject fields
            // Base subject fields with timestamps
            $now = new UTCDateTime;
            $fields = [
                'project_id' => $projectId,
                'ocr' => '',
                'expedition_ids' => [],
                'exported' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Embed occurrence data if available
            $occurrence = [];
            if ($occurrenceId) {
                if (is_array($occurrenceData)) {
                    // Memory-based lookup
                    if (isset($occurrenceData[$occurrenceId])) {
                        $occurrence = ['occurrence' => $occurrenceData[$occurrenceId]];
                    } else {
                        $occurrence = ['occurrence' => ['id' => (string) $occurrenceId]];
                    }
                } elseif (is_string($occurrenceData)) {
                    // Use batched occurrences if available, otherwise fallback to individual lookup
                    if (! empty($batchedOccurrences) && isset($batchedOccurrences[$occurrenceId])) {
                        $occurrence = ['occurrence' => $batchedOccurrences[$occurrenceId]];
                    } else {
                        // Fallback to individual lookup for backward compatibility
                        $occurrenceRecord = ImportOccurrence::findByOccurrenceId($occurrenceId, $occurrenceData);
                        if ($occurrenceRecord) {
                            $occurrence = ['occurrence' => $occurrenceRecord];
                        } else {
                            $occurrence = ['occurrence' => ['id' => (string) $occurrenceId]];
                        }
                    }

                    // If no occurrence found, create minimal occurrence record
                    if (empty($occurrence)) {
                        $occurrence = ['occurrence' => ['id' => (string) $occurrenceId]];
                    }
                }
            }

            // Combine all data
            $subject = $fields + $row + $occurrence;

            // Set MongoDB ObjectId
            $subject['_id'] = new ObjectId;

            return $subject;

        } catch (Exception $e) {
            Log::error('Error building subject', [
                'error' => $e->getMessage(),
                'row' => $row,
            ]);

            $rejected = ['Reason' => 'Error building subject: '.$e->getMessage()] + $row;
            $this->rejectedMedia[] = $rejected;

            return null;
        }
    }

    /**
     * Bulk insert subjects using MongoDB operations with optimized write concern.
     */
    private function bulkInsertSubjects(array $subjects): void
    {
        try {
            // Use MongoDB bulk operations with optimized write concern for speed
            Subject::raw(function ($collection) use ($subjects) {
                return $collection->insertMany($subjects, [
                    'writeConcern' => new WriteConcern(0), // Fire and forget for speed
                    'ordered' => false, // Allow parallel processing
                ]);
            });

            $this->subjectCount += count($subjects);

        } catch (Exception $e) {
            Log::error('Bulk insert failed', [
                'error' => $e->getMessage(),
                'subject_count' => count($subjects),
            ]);

            // Fallback to individual inserts
            foreach ($subjects as $subject) {
                try {
                    Subject::create($subject);
                } catch (Exception $individualError) {
                    Log::error('Individual subject insert failed', [
                        'error' => $individualError->getMessage(),
                        'subject_id' => $subject['imageId'] ?? 'unknown',
                    ]);

                    $rejected = ['Reason' => 'Database insert failed: '.$individualError->getMessage()];
                    $this->rejectedMedia[] = $rejected;
                }
            }
        }
    }

    /**
     * Batch lookup occurrences to reduce individual database queries.
     */
    private function batchLookupOccurrences(array $occurrenceIds): array
    {
        if (empty($occurrenceIds)) {
            return [];
        }

        $occurrences = [];
        $occurrenceResults = ImportOccurrence::where('import_session_id', $this->importSessionId)
            ->whereIn('occurrence_id', array_unique($occurrenceIds))
            ->get();

        foreach ($occurrenceResults as $occ) {
            $occurrences[$occ->occurrence_id] = $occ->data;
        }

        return $occurrences;
    }

    /**
     * Save header array for property creation.
     */
    private function saveHeaderArray(array $header, string $headerType): void
    {
        try {
            $result = $this->headerService->getFirst('project_id', $this->projectId);

            if (empty($result)) {
                $insert = [
                    'project_id' => $this->projectId,
                    'header' => [$headerType => $header],
                ];
                $this->headerService->create($insert);
            } else {
                $existingHeader = $result->header;
                $existingHeader[$headerType] = isset($existingHeader[$headerType]) ? $this->combineHeader($existingHeader[$headerType], $header) : array_unique($header);
                $result->header = $existingHeader;
                $result->save();
            }
        } catch (Exception $e) {
            Log::error('Failed to save header array', ['error' => $e->getMessage(), 'headerType' => $headerType]);
        }
    }

    /**
     * Combine saved header with new header.
     */
    private function combineHeader(array $resHeader, array $newHeader): array
    {
        return array_unique(array_merge($resHeader, array_diff($newHeader, $resHeader)));
    }

    /**
     * Get duplicates for reporting.
     */
    public function getDuplicates(): array
    {
        return $this->duplicates;
    }

    /**
     * Get rejected media for reporting.
     */
    public function getRejectedMedia(): array
    {
        return $this->rejectedMedia;
    }
}
