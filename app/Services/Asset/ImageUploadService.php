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
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageUploadService
{
    /**
     * Configuration for image variants by model type
     */
    protected array $variantConfigs = [
        'Profile' => [
            'medium' => ['width' => 160, 'height' => 160],
            'small' => ['width' => 25, 'height' => 25],
        ],
        'Expedition' => [
            'medium' => ['width' => 318, 'height' => 208],
        ],
        'Project' => [], // No variants for projects
    ];

    /**
     * Upload image file and create variants based on model type
     *
     * @param  UploadedFile|TemporaryUploadedFile  $file
     * @return string|null The uploaded file path or null on failure
     */
    public function uploadImage($file, string $modelType, string $storagePath, ?string $oldFilePath = null): ?string
    {
        try {
            // Delete old file if provided
            if ($oldFilePath) {
                $this->deleteImage($oldFilePath, $modelType);
            }

            $disk = 's3';
            $filename = time().'_'.$file->getClientOriginalName();
            $variants = $this->variantConfigs[$modelType] ?? [];

            // Determine storage path - use 'original' subdirectory if variants are configured
            $originalStoragePath = ! empty($variants) ? $storagePath.'/original' : $storagePath;

            // Store original image on S3
            $originalPath = $file->storeAs($originalStoragePath, $filename, $disk);

            // Verify file was uploaded to S3
            if (! Storage::disk($disk)->exists($originalPath)) {
                throw new \Exception('Failed to upload file to S3');
            }

            // Create variants if configured
            if (! empty($variants)) {
                $this->createVariants($file, $filename, $storagePath, $variants, $disk);
            }

            return $originalPath;

        } catch (\Exception $e) {
            \Log::error('Image upload failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Create image variants
     *
     * @param  UploadedFile|TemporaryUploadedFile  $file
     */
    protected function createVariants($file, string $filename, string $baseStoragePath, array $variants, string $disk): void
    {
        foreach ($variants as $variant => $dimensions) {
            try {
                // Create intervention image from uploaded file
                $image = Image::read($file->getRealPath());

                // Resize image maintaining aspect ratio
                $image->resize($dimensions['width'], $dimensions['height']);

                // Create variant path
                $variantPath = $baseStoragePath.'/'.$variant.'/'.$filename;

                // Encode image and store
                $imageData = (string) $image->encode();
                Storage::disk($disk)->put($variantPath, $imageData);

            } catch (\Exception $e) {
                // Log error but don't fail the main upload
                \Log::warning("Failed to create {$variant} variant: ".$e->getMessage());
            }
        }
    }

    /**
     * Delete image and its variants
     */
    public function deleteImage(string $filePath, string $modelType): void
    {
        try {
            $disk = 's3';
            $variants = $this->variantConfigs[$modelType] ?? [];

            // Delete original file
            if (Storage::disk($disk)->exists($filePath)) {
                Storage::disk($disk)->delete($filePath);
            }

            // Delete variants if they exist
            if (! empty($variants) && str_contains($filePath, '/original/')) {
                foreach (array_keys($variants) as $variant) {
                    $variantPath = str_replace('/original/', '/'.$variant.'/', $filePath);
                    if (Storage::disk($disk)->exists($variantPath)) {
                        Storage::disk($disk)->delete($variantPath);
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::warning("Failed to delete image {$filePath}: ".$e->getMessage());
        }
    }

    /**
     * Get variant configurations for a model type
     */
    public function getVariantConfig(string $modelType): array
    {
        return $this->variantConfigs[$modelType] ?? [];
    }

    /**
     * Check if a model type requires variants
     */
    public function requiresVariants(string $modelType): bool
    {
        return ! empty($this->variantConfigs[$modelType] ?? []);
    }
}
