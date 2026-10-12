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

namespace App\Services\Asset;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AssetUploadService
{
    /**
     * Storage paths for different asset types
     */
    protected array $storageConfigs = [
        'ProjectAsset' => 'config.uploads.project-assets',
        'SiteAsset' => 'config.uploads.site-assets',
    ];

    /**
     * Upload asset file to S3
     *
     * @param  UploadedFile|TemporaryUploadedFile  $file
     * @return string|null The uploaded file path or null on failure
     */
    public function uploadAsset($file, string $modelType, ?string $storagePath = null, ?string $oldFilePath = null): ?string
    {
        try {
            // Delete old file if provided
            if ($oldFilePath) {
                $this->deleteAsset($oldFilePath);
            }

            $disk = 's3';
            $filename = time().'_'.$file->getClientOriginalName();

            // Use provided storage path or get from configuration
            $uploadPath = $storagePath ?? $this->getStoragePath($modelType);

            // Store file on S3
            $filePath = $file->storeAs($uploadPath, $filename, $disk);

            // Verify file was uploaded to S3
            if (! Storage::disk($disk)->exists($filePath)) {
                throw new \Exception('Failed to upload file to S3');
            }

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Asset upload failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Delete asset file from S3 storage
     */
    public function deleteAsset(string $filePath): void
    {
        try {
            $disk = 's3';

            if (Storage::disk($disk)->exists($filePath)) {
                Storage::disk($disk)->delete($filePath);
            }

        } catch (\Exception $e) {
            Log::warning("Failed to delete asset file {$filePath}: ".$e->getMessage());
        }
    }

    /**
     * Validate that the file exists on S3 storage
     */
    public function validateFileExists(string $filePath, string $modelType, int $modelId): void
    {
        $disk = 's3';

        // Verify file exists on S3
        if (! Storage::disk($disk)->exists($filePath)) {
            Log::warning("{$modelType} file does not exist on S3: {$filePath} for {$modelType} ID: {$modelId}");
        }
    }

    /**
     * Get storage path for model type
     */
    public function getStoragePath(string $modelType): string
    {
        $configKey = $this->storageConfigs[$modelType] ?? null;

        if (! $configKey) {
            throw new \InvalidArgumentException("Unknown model type: {$modelType}");
        }

        return config($configKey, 'uploads/general');
    }

    /**
     * Get supported file types for asset uploads
     */
    public function getSupportedFileTypes(): array
    {
        return [
            'application/pdf',
            'image/*',
            'text/*',
            '.xlsx', '.xls', '.csv',
            '.zip', '.rar',
            '.doc', '.docx',
        ];
    }

    /**
     * Get max file size for asset uploads (in KB)
     */
    public function getMaxFileSize(): int
    {
        return 10240; // 10MB
    }
}
