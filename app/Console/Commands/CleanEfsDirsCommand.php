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
    protected $description = 'Deletes files and empty directories older than 72 hours from the /efs directories BIOSPEX writes to (import, scratch, GeoLocate export). Skipped while an import, export, OCR run or GeoLocate job is in progress.';

    private int $deletedFiles = 0;

    private int $deletedDirectories = 0;

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

        $threshold = Carbon::now()->subHours(72)->getTimestamp();

        foreach ($this->directories() as $directory) {
            if (File::isDirectory($directory)) {
                $this->cleanDirectory($directory, $threshold);
            }
        }

        Log::info("Cleanup completed. Files deleted: {$this->deletedFiles}. Directories deleted: {$this->deletedDirectories}.");
    }

    /**
     * Get the /efs directories that BIOSPEX writes to.
     *
     * /efs is shared by both servers and holds other files too, such as database dumps, so nothing outside these
     * directories is touched.
     *
     * @return list<string>
     */
    private function directories(): array
    {
        $root = config('filesystems.disks.efs.root');

        return [
            "$root/".config('config.import_dir'),
            "$root/".config('config.scratch_dir'),
            "$root/".config('geolocate.dir.export'),
        ];
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
     * Recursively delete the files, and then the empty subdirectories, last modified before the threshold.
     *
     * The directory itself is kept. A subdirectory emptied by this run was just modified, so it's removed by a
     * later run. Hidden files aren't deleted, and a subdirectory holding one isn't empty: on EFS a deleted file
     * that is still open becomes a hidden .nfs file until it's closed.
     */
    private function cleanDirectory(string $path, int $threshold): void
    {
        foreach (File::files($path) as $file) {
            if (File::lastModified($file) < $threshold) {
                File::delete($file);
                $this->deletedFiles++;
            }
        }

        foreach (File::directories($path) as $directory) {
            $this->cleanDirectory($directory, $threshold);

            if (File::isEmptyDirectory($directory) && File::lastModified($directory) < $threshold && File::deleteDirectory($directory)) {
                $this->deletedDirectories++;
            }
        }
    }
}
