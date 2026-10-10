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

use App\Models\ExportQueue;
use App\Models\Import;
use App\Models\OcrQueue;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;

class CleanEfsDirsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-efs-dirs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes files older than 72 hours from the /efs directory, leaving empty directories intact. Skipped while an import, export, OCR run or GeoLocate job is in progress.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $running = $this->runningProcesses();

        if ($running !== []) {
            $message = 'Skipped /efs cleanup: '.implode(', ', $running).' in progress.';
            Log::info($message);
            $this->info($message);

            return;
        }

        $deletedFiles = $this->cleanDirectory(config('filesystems.disks.efs.root'));

        Log::info("Cleanup completed. Files deleted: $deletedFiles");
    }

    /**
     * Get the processes that may be using files under /efs.
     *
     * Failed imports, exports and OCR runs don't count, so one stuck failure can't stop the cleanup for good.
     *
     * @return list<string>
     */
    private function runningProcesses(): array
    {
        $checks = [
            'import' => fn (): bool => Import::where('error', 0)->exists() || $this->queueHasJobs('import'),
            'export' => fn (): bool => ExportQueue::where('error', 0)->exists() || $this->queueHasJobs('export'),
            'OCR' => fn (): bool => OcrQueue::where('error', 0)->exists() || $this->queueHasJobs('ocr'),
            'GeoLocate' => fn (): bool => $this->queueHasJobs('geolocate'),
        ];

        return array_keys(array_filter($checks, fn (callable $check): bool => $check()));
    }

    /**
     * Whether a queue has waiting, delayed or running jobs. If the queue can't be read, assume it has.
     */
    private function queueHasJobs(string $queue): bool
    {
        try {
            return Queue::size(config("config.queue.$queue")) > 0;
        } catch (Throwable $throwable) {
            Log::warning("app:clean-efs-dirs could not read the $queue queue: {$throwable->getMessage()}");

            return true;
        }
    }

    /**
     * Recursively clean files older than 72 hours in a directory.
     */
    private function cleanDirectory(string $path): int
    {
        $deletedFiles = 0;

        // Iterate through directory contents
        $items = File::files($path);
        $directories = File::directories($path);

        // Delete files older than 72 hours
        foreach ($items as $file) {
            $lastModified = Carbon::createFromTimestamp(File::lastModified($file));
            $threshold = Carbon::now()->subHours(72);

            if ($lastModified->lessThan($threshold)) {
                File::delete($file); // Use the File facade to delete
                $deletedFiles++;
            }
        }

        // Recursively scan subdirectories
        foreach ($directories as $directory) {
            $deletedFiles += $this->cleanDirectory($directory);
        }

        return $deletedFiles;
    }
}
