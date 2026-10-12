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

use App\Jobs\DwcBatchImportJob;
use App\Jobs\TesseractOcrCreateJob;
use App\Models\Import;
use App\Models\Project;
use App\Models\Subject;
use App\Notifications\Generic;
use App\Services\Process\CreateReportService;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Subject::truncate();
    Storage::fake('efs');
    Queue::fake();
    Notification::fake();
    // The rejected-rows report is streamed straight to S3.
    $this->mock(CreateReportService::class)->shouldReceive('createCsvReport')->andReturn(base64_encode('report.csv'));

    $this->project = Project::factory()->create();
});

afterEach(function () {
    Subject::truncate();
});

/**
 * Zip the given fixture files onto the fake efs disk and create an import for it.
 *
 * @param  array<int, string>  $files
 */
function dwcImport(Project $project, array $files = ['meta.xml', 'occurrence.csv', 'multimedia.csv'], array $attributes = []): Import
{
    Storage::disk('efs')->makeDirectory('imports');
    $zipPath = Storage::disk('efs')->path('imports/archive.zip');

    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($files as $file) {
        $zip->addFile(base_path("tests/Fixtures/dwc/basic/{$file}"), $file);
    }
    $zip->close();

    return Import::factory()->create(array_merge([
        'project_id' => $project->id,
        'file' => 'imports/archive.zip',
        'processing' => 0,
        'error' => 0,
    ], $attributes));
}

function runImportJob(Import $import, int $attempt = 1): FakeJob
{
    $queueJob = new FakeJob;
    $queueJob->attempts = $attempt;

    $job = new DwcBatchImportJob($import);
    $job->setJob($queueJob);
    app()->call([$job, 'handle']);

    return $queueJob;
}

it('imports the archive, notifies the group owner and queues OCR', function () {
    $import = dwcImport($this->project);

    runImportJob($import);

    expect(Subject::where('project_id', $this->project->id)->count())->toBe(3)
        ->and(Import::find($import->id))->toBeNull();
    Storage::disk('efs')->assertMissing('imports/archive.zip');
    Queue::assertPushed(TesseractOcrCreateJob::class);
    Notification::assertSentTo($this->project->group->owner, Generic::class);
});

it('sends the owner a separate report of duplicate images', function () {
    Subject::create(['project_id' => $this->project->id, 'imageId' => '3d6a40dc-4bc3-13f4-aa5b-0038f5eec649']);
    $reports = [];
    $this->mock(CreateReportService::class)->shouldReceive('createCsvReport')
        ->andReturnUsing(function (string $name, array $rows) use (&$reports) {
            $reports[$name] = $rows;

            return base64_encode($name);
        });
    $import = dwcImport($this->project);

    runImportJob($import);

    $duplicatesReport = collect($reports)->first(fn ($rows, $name) => str_ends_with($name, '_duplicates.csv'));
    $rejectedReport = collect($reports)->first(fn ($rows, $name) => str_ends_with($name, '_rejected.csv'));

    expect($duplicatesReport)->toHaveCount(1)
        ->and($duplicatesReport[0]['Reason'])->toBe('Duplicate imageId in database.')
        ->and($rejectedReport)->toHaveCount(2);
    Notification::assertSentTo($this->project->group->owner, Generic::class, function (Generic $notification) {
        $email = json_encode((fn () => $this->attributes)->call($notification));

        return str_contains($email, 'Duplicate records: 1')
            && str_contains($email, 'View Duplicate Records (1)')
            && str_contains($email, 'Rejected records: 2');
    });
});

it('skips an import that is already being processed', function () {
    $import = dwcImport($this->project, attributes: ['processing' => 1]);

    runImportJob($import);

    expect(Subject::count())->toBe(0);
    Queue::assertNothingPushed();
    Notification::assertNothingSent();
});

it('retries an archive without meta.xml on the first attempt', function () {
    $import = dwcImport($this->project, ['occurrence.csv', 'multimedia.csv']);

    $queueJob = runImportJob($import, attempt: 1);

    expect($queueJob->isReleased())->toBeTrue()
        ->and($import->fresh())->error->toBe(0)->processing->toBe(0);
    Notification::assertNothingSent();
});

it('marks the import as failed and tells the owner on the last attempt', function () {
    $import = dwcImport($this->project, ['occurrence.csv', 'multimedia.csv']);

    $queueJob = runImportJob($import, attempt: 2);

    expect(Subject::count())->toBe(0)
        ->and($queueJob->isDeleted())->toBeTrue()
        ->and($import->fresh())->error->toBe(1)->processing->toBe(0);
    Queue::assertNothingPushed();
    Notification::assertSentTo($this->project->group->owner, Generic::class);
});
