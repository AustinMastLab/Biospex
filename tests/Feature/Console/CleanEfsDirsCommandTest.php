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

beforeEach(function () {
    Queue::fake();

    $this->efs = sys_get_temp_dir().'/efs-test-'.uniqid();
    config(['filesystems.disks.efs.root' => $this->efs]);

    File::makeDirectory("{$this->efs}/import/scratch", recursive: true);
    $this->oldFile = "{$this->efs}/import/scratch/old.csv";
    $this->newFile = "{$this->efs}/import/new.csv";
    File::put($this->oldFile, 'old');
    File::put($this->newFile, 'new');
    touch($this->oldFile, now()->subHours(73)->getTimestamp());
});

afterEach(function () {
    File::deleteDirectory($this->efs);
});

it('deletes files older than 72 hours and keeps newer files and every directory', function () {
    $this->artisan('app:clean-efs-dirs')->assertSuccessful();

    expect(File::exists($this->oldFile))->toBeFalse()
        ->and(File::exists($this->newFile))->toBeTrue()
        ->and(File::isDirectory("{$this->efs}/import/scratch"))->toBeTrue();
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
