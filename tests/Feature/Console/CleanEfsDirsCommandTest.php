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

use App\Jobs\GeoLocateExportJob;
use App\Models\ExportQueue;
use App\Models\Import;
use App\Models\OcrQueue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

/**
 * Create a file (with its directories) under the test /efs root, last modified the given number of hours ago.
 */
function efsFile(string $root, string $path, int $hoursAgo): string
{
    $file = "$root/$path";
    File::ensureDirectoryExists(dirname($file));
    File::put($file, 'data');
    touch($file, now()->subHours($hoursAgo)->getTimestamp());

    return $file;
}

/**
 * Create an empty directory under the test /efs root, last modified the given number of hours ago.
 */
function efsDirectory(string $root, string $path, int $hoursAgo): string
{
    $directory = "$root/$path";
    File::ensureDirectoryExists($directory);
    touch($directory, now()->subHours($hoursAgo)->getTimestamp());

    return $directory;
}

beforeEach(function () {
    Queue::fake();

    $this->efs = sys_get_temp_dir().'/efs-test-'.uniqid();
    config(['filesystems.disks.efs.root' => $this->efs]);

    $this->oldFile = efsFile($this->efs, 'import/old.zip', 73);
});

afterEach(function () {
    File::deleteDirectory($this->efs);
});

it('deletes files older than 72 hours in the import, scratch and GeoLocate export directories', function () {
    $oldScratch = efsFile($this->efs, 'scratch/0f1e2d/occurrence.csv', 73);
    $oldGeoLocate = efsFile($this->efs, 'geolocate/export/12.csv', 73);
    $newImport = efsFile($this->efs, 'import/new.zip', 71);

    $this->artisan('app:clean-efs-dirs')->assertSuccessful();

    expect(File::exists($this->oldFile))->toBeFalse()
        ->and(File::exists($oldScratch))->toBeFalse()
        ->and(File::exists($oldGeoLocate))->toBeFalse()
        ->and(File::exists($newImport))->toBeTrue();
});

it('leaves everything outside those directories alone', function () {
    $dump = efsFile($this->efs, 'mongodb/biospex.gz', 200);
    $otherSite = efsFile($this->efs, 'public/team_image/photo.jpg', 200);
    $otherDirectory = efsDirectory($this->efs, 'zooniverse/reconciled', 200);

    $this->artisan('app:clean-efs-dirs')->assertSuccessful();

    expect(File::exists($dump))->toBeTrue()
        ->and(File::exists($otherSite))->toBeTrue()
        ->and(File::isDirectory($otherDirectory))->toBeTrue();
});

it('removes empty subdirectories older than 72 hours, but keeps newer ones and the directories themselves', function () {
    $oldEmpty = efsDirectory($this->efs, 'scratch/4f2883', 73);
    $newEmpty = efsDirectory($this->efs, 'scratch/6b0536', 71);
    $holdsOpenFile = efsDirectory($this->efs, 'scratch/9a8b7c', 73);
    File::put("$holdsOpenFile/.nfs000001", 'still open');
    touch($holdsOpenFile, now()->subHours(73)->getTimestamp());
    efsDirectory($this->efs, 'geolocate/export', 73);

    $this->artisan('app:clean-efs-dirs')->assertSuccessful();

    expect(File::isDirectory($oldEmpty))->toBeFalse()
        ->and(File::isDirectory($newEmpty))->toBeTrue()
        ->and(File::isDirectory($holdsOpenFile))->toBeTrue()
        ->and(File::isDirectory("{$this->efs}/import"))->toBeTrue()
        ->and(File::isDirectory("{$this->efs}/scratch"))->toBeTrue()
        ->and(File::isDirectory("{$this->efs}/geolocate/export"))->toBeTrue();
});

it('skips the cleanup while a process is in progress', function (Closure $start, string $process) {
    $start();

    $this->artisan('app:clean-efs-dirs')
        ->expectsOutput("Skipped /efs cleanup: {$process} in progress.")
        ->assertSuccessful();

    expect(File::exists($this->oldFile))->toBeTrue();
})->with([
    'an import' => [fn () => Import::factory()->create(['error' => 0]), 'import'],
    'an export' => [fn () => ExportQueue::factory()->create(['error' => 0]), 'export'],
    'an OCR run' => [fn () => OcrQueue::factory()->create(['error' => 0]), 'OCR'],
    'a GeoLocate job' => [fn () => Queue::pushOn('geolocate', GeoLocateExportJob::class), 'GeoLocate'],
]);

it('isn\'t held up by failed imports, exports or OCR runs', function () {
    Import::factory()->create(['error' => 1]);
    ExportQueue::factory()->create(['error' => 1]);
    OcrQueue::factory()->create(['error' => 1]);

    $this->artisan('app:clean-efs-dirs')->assertSuccessful();

    expect(File::exists($this->oldFile))->toBeFalse();
});
