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

namespace App\Services\Helpers;

use App\Models\PanoptesTranscription;
use App\Models\PusherTranscription;
use Illuminate\Support\Facades\Log;

/**
 * Class TranscriptionMapService
 */
class TranscriptionMapService
{
    /**
     * TranscriptionMapService construct
     */
    public function __construct(protected array $reservedEncoded, protected array $mappedTranscriptionFields) {}

    /**
     * Map transcription fields that are varied in database.
     *
     * @return mixed|string
     *
     * @throws \Throwable
     */
    public function mapTranscriptionField(
        string $type,
        PanoptesTranscription $panoptesTranscription,
        ?PusherTranscription $pusherTranscription = null
    ): mixed {
        try {
            foreach ($this->mappedTranscriptionFields[$type] as $value) {
                $encodedValue = $this->encodeTranscriptionField($value);
                if (isset($panoptesTranscription->{$encodedValue})) {
                    return $panoptesTranscription->{$encodedValue};
                }
            }

            if ($pusherTranscription === null) {
                return '';
            }

            return $pusherTranscription->transcriptionContent[$type] ?? '';
        } catch (\Throwable $e) {
            Log::error('Error in mapTranscriptionField method', [
                'type' => $type,
                'panoptes_transcription_id' => $panoptesTranscription->id ?? 'unknown',
                'pusher_transcription_id' => $pusherTranscription->id ?? 'unknown',
                'mapped_fields' => $this->mappedTranscriptionFields[$type] ?? [],
                'current_value' => $value ?? 'undefined',
                'current_encoded_value' => $encodedValue ?? 'undefined',
                'encoded_value_type' => isset($encodedValue) ? gettype($encodedValue) : 'undefined',
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            throw $e; // Re-throw to prevent database operations
        }
    }

    /**
     * Encode transcription an reconcile fields.
     */
    public function encodeTranscriptionField(string $field): string
    {
        if (str_contains($field, 'subject_') || in_array($field, $this->reservedEncoded)) {
            return $field;
        }

        return $this->base64UrlEncode($field);
    }

    /**
     * Base encode string.
     */
    public function base64UrlEncode(string $bin): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($bin));
    }

    /**
     * Decode transcription an reconcile fields.
     */
    public function decodeTranscriptionField(string $field): false|string
    {
        if (str_contains($field, 'subject_') || in_array($field, $this->reservedEncoded)) {
            return $field;
        }

        $decoded = $this->base64UrlDecode($field);

        // If decoding yields garbage or non-UTF8, it probably wasn't encoded
        if ($decoded === false || ! mb_check_encoding($decoded, 'UTF-8')) {
            return $field;
        }

        return $decoded;
    }

    /**
     * Base decode string.
     */
    public function base64UrlDecode(string $str): false|string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $str));
    }
}
