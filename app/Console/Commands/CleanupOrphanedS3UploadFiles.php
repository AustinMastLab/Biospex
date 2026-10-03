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

namespace App\Console\Commands;

use App\Models\Expedition;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectAsset;
use App\Models\SiteAsset;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Cleanup orphaned files from S3 that are not referenced in database records
 */
class CleanupOrphanedS3UploadFiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'files:cleanup-orphaned 
                          {--dry-run : Show what would be deleted without actually deleting}
                          {--older-than=24 : Only delete files older than X hours (default: 24)}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up orphaned files in S3 that are not referenced in database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $olderThanHours = (int) $this->option('older-than');
        $cutoffTime = now()->subHours($olderThanHours);

        $this->info('Cleanup Orphaned S3 Upload Files Command');
        $this->line('============================');
        $this->line('Mode: '.($dryRun ? 'DRY RUN (no files will be deleted)' : 'LIVE RUN (files will be deleted)'));
        $this->line("Cutoff time: Files older than {$olderThanHours} hours ({$cutoffTime})");
        $this->newLine();

        // Get all referenced file paths from database
        $referencedFiles = $this->getReferencedFiles();
        $this->info('Found '.count($referencedFiles).' files referenced in database');

        // Check each upload directory
        $directories = [
            config('config.uploads.project_logos'),
            config('config.uploads.expedition_logos'),
            config('config.uploads.profile_avatars'),
            config('config.uploads.profile_avatars_medium'),
            config('config.uploads.profile_avatars_original'),
            config('config.uploads.profile_avatars_small'),
            config('config.uploads.project-assets'),
            config('config.uploads.site-assets'),
        ];

        $totalOrphaned = 0;
        $totalDeleted = 0;

        foreach ($directories as $directory) {
            $this->newLine();
            $this->info("Checking directory: {$directory}");
            $this->line('----------------------------------------');

            $orphanedCount = $this->cleanupDirectory($directory, $referencedFiles, $cutoffTime, $dryRun, $totalDeleted);
            $totalOrphaned += $orphanedCount;
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line('--------');
        $this->line("Total orphaned files found: {$totalOrphaned}");

        if ($dryRun) {
            $this->warn('DRY RUN: No files were actually deleted');
            $this->line('Run without --dry-run to actually delete the orphaned files');
        } else {
            $this->info("Total files deleted: {$totalDeleted}");
        }
    }

    /**
     * Get all file paths referenced in database records
     */
    private function getReferencedFiles(): array
    {
        $referencedFiles = [];

        // Project logos
        $projectLogos = Project::whereNotNull('logo_path')
            ->pluck('logo_path')
            ->filter()
            ->toArray();
        $referencedFiles = array_merge($referencedFiles, $projectLogos);

        // Expedition logos
        $expeditionLogos = Expedition::whereNotNull('logo_path')
            ->pluck('logo_path')
            ->filter()
            ->toArray();
        $referencedFiles = array_merge($referencedFiles, $expeditionLogos);

        // Profile avatars
        $profileAvatars = Profile::whereNotNull('avatar_path')
            ->pluck('avatar_path')
            ->filter()
            ->toArray();
        $referencedFiles = array_merge($referencedFiles, $profileAvatars);

        // Project site-asset downloads
        $projectResourceDownloads = ProjectAsset::whereNotNull('download_path')
            ->pluck('download_path')
            ->filter()
            ->toArray();
        $referencedFiles = array_merge($referencedFiles, $projectResourceDownloads);

        // Resource documents
        $resourceDocuments = SiteAsset::whereNotNull('download_path')
            ->pluck('download_path')
            ->filter()
            ->toArray();
        $referencedFiles = array_merge($referencedFiles, $resourceDocuments);

        return array_unique($referencedFiles);
    }

    /**
     * Clean up orphaned files in a specific directory
     */
    private function cleanupDirectory(string $directory, array $referencedFiles, $cutoffTime, bool $dryRun, int &$totalDeleted): int
    {
        try {
            $files = Storage::disk('s3')->files($directory);
            $orphanedCount = 0;

            if (empty($files)) {
                $this->line('  No files found in directory');

                return 0;
            }

            $this->line('  Found '.count($files).' files in directory');

            foreach ($files as $file) {
                // Skip if file is referenced in database
                if (in_array($file, $referencedFiles)) {
                    continue;
                }

                // Check file age
                try {
                    $lastModified = Storage::disk('s3')->lastModified($file);
                    $fileDate = Carbon::createFromTimestamp($lastModified);

                    if ($fileDate->greaterThan($cutoffTime)) {
                        $this->line("  Skipping recent file: {$file} (modified: {$fileDate})");

                        continue;
                    }
                } catch (\Exception $e) {
                    $this->warn("  Could not get modification time for {$file}: ".$e->getMessage());

                    continue;
                }

                // File is orphaned and old enough to delete
                $orphanedCount++;

                if ($dryRun) {
                    $this->line("  [DRY RUN] Would delete: {$file}");
                } else {
                    try {
                        Storage::disk('s3')->delete($file);
                        $totalDeleted++;
                        $this->info("  Deleted: {$file}");
                    } catch (\Exception $e) {
                        $this->error("  Failed to delete {$file}: ".$e->getMessage());
                    }
                }
            }

            if ($orphanedCount === 0) {
                $this->line('  No orphaned files found in this directory');
            }

            return $orphanedCount;

        } catch (\Exception $e) {
            $this->error("Error processing directory {$directory}: ".$e->getMessage());

            return 0;
        }
    }
}
