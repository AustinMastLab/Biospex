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

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Deletes files older than 72 hours from the /efs directory, leaving empty directories intact';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $directory = '/efs';

        $deletedFiles = $this->cleanDirectory($directory);

        Log::info("Cleanup completed. Files deleted: $deletedFiles");
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
