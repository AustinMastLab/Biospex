<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
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
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Cleanup orphaned files from S3 that are not referenced in database records.
 *
 * Each upload directory is scanned including subdirectories, so files left in
 * retired layouts (such as the old expedition logos/original and logos/medium
 * variants) are treated as orphans once nothing references them.
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
     * Avatar sizes stored beside the original upload.
     *
     * @var array<int, string>
     */
    private const AVATAR_VARIANTS = ['medium', 'small'];

    /**
     * Whether any directory could not be listed or any file could not be deleted.
     */
    private bool $hadErrors = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $olderThanHours = (int) $this->option('older-than');
        $cutoffTime = now()->subHours($olderThanHours);

        $this->info('Cleanup Orphaned S3 Upload Files Command');
        $this->line('============================');
        $this->line('Mode: '.($dryRun ? 'DRY RUN (no files will be deleted)' : 'LIVE RUN (files will be deleted)'));
        $this->line("Cutoff time: Files older than {$olderThanHours} hours ({$cutoffTime})");
        $this->newLine();

        $referencedFiles = $this->getReferencedFiles();
        $this->info('Found '.count($referencedFiles).' files referenced in database');

        $directories = [
            config('config.uploads.project_logos'),
            config('config.uploads.expedition_logos'),
            config('config.uploads.profile_avatars'),
            config('config.uploads.project-assets'),
            config('config.uploads.site-assets'),
        ];

        $totalOrphaned = 0;
        $totalDeleted = 0;

        foreach ($directories as $directory) {
            $this->newLine();
            $this->info("Checking directory: {$directory}");
            $this->line('----------------------------------------');

            $totalOrphaned += $this->cleanupDirectory($directory, $referencedFiles, $cutoffTime, $dryRun, $totalDeleted);
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

        if ($this->hadErrors) {
            $this->error('Some directories or files could not be processed; see errors above. Results are incomplete.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Get all file paths referenced in database records, keyed by path for fast lookup.
     *
     * @return array<string, true>
     */
    private function getReferencedFiles(): array
    {
        $avatarOriginals = Profile::whereNotNull('avatar_path')->pluck('avatar_path')->filter();

        $avatarVariants = $avatarOriginals
            ->filter(fn (string $path) => str_contains($path, '/original/'))
            ->flatMap(fn (string $path) => array_map(
                fn (string $variant) => str_replace('/original/', "/{$variant}/", $path),
                self::AVATAR_VARIANTS,
            ));

        return collect()
            ->merge(Project::whereNotNull('logo_path')->pluck('logo_path'))
            ->merge(Expedition::whereNotNull('logo_path')->pluck('logo_path'))
            ->merge($avatarOriginals)
            ->merge($avatarVariants)
            ->merge(ProjectAsset::whereNotNull('download_path')->pluck('download_path'))
            ->merge(SiteAsset::whereNotNull('download_path')->pluck('download_path'))
            ->filter()
            ->mapWithKeys(fn (string $path) => [$path => true])
            ->all();
    }

    /**
     * Clean up orphaned files in a directory and its subdirectories.
     *
     * @param  array<string, true>  $referencedFiles
     */
    private function cleanupDirectory(string $directory, array $referencedFiles, CarbonInterface $cutoffTime, bool $dryRun, int &$totalDeleted): int
    {
        try {
            $files = array_filter(
                Storage::disk('s3')->allFiles($directory),
                fn (string $file) => ! str_starts_with(basename($file), '.'),
            );
            $orphanedCount = 0;

            if (empty($files)) {
                $this->line('  No files found in directory');

                return 0;
            }

            $this->line('  Found '.count($files).' files in directory');

            foreach ($files as $file) {
                if (isset($referencedFiles[$file])) {
                    continue;
                }

                try {
                    $fileDate = Carbon::createFromTimestamp(Storage::disk('s3')->lastModified($file));

                    if ($fileDate->greaterThan($cutoffTime)) {
                        $this->line("  Skipping recent file: {$file} (modified: {$fileDate})");

                        continue;
                    }
                } catch (\Exception $e) {
                    $this->warn("  Could not get modification time for {$file}: ".$e->getMessage());

                    continue;
                }

                $orphanedCount++;

                if ($dryRun) {
                    $this->line("  [DRY RUN] Would delete: {$file}");

                    continue;
                }

                try {
                    Storage::disk('s3')->delete($file);
                    $totalDeleted++;
                    $this->info("  Deleted: {$file}");
                } catch (\Exception $e) {
                    $this->hadErrors = true;
                    $this->error("  Failed to delete {$file}: ".$e->getMessage());
                }
            }

            if ($orphanedCount === 0) {
                $this->line('  No orphaned files found in this directory');
            }

            return $orphanedCount;
        } catch (\Exception $e) {
            $this->hadErrors = true;
            $this->error("Error processing directory {$directory}: ".$e->getMessage());

            return 0;
        }
    }
}
