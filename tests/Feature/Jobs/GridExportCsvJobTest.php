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

use App\Jobs\GridExportCsvJob;
use App\Models\Header;
use App\Models\Project;
use App\Models\Subject;
use App\Models\User;
use App\Services\Api\AwsS3ApiService;
use App\Services\Grid\JqGridEncoder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Subject::truncate();
    Notification::fake();
    config(['filesystems.disks.s3.bucket' => 'test-bucket']);
    Storage::fake('s3');
    Storage::disk('s3')->makeDirectory(config('config.report_dir'));

    // The CSV is streamed to S3; write it to the faked disk instead.
    $this->mock(AwsS3ApiService::class)
        ->shouldReceive('createS3BucketStream')
        ->andReturnUsing(fn ($bucket, $path, $mode) => fopen(Storage::disk('s3')->path($path), $mode));

    $this->project = Project::factory()->create();
    Header::create(['project_id' => $this->project->id, 'header' => ['image' => ['accessURI']]]);
});

afterEach(function () {
    Subject::truncate();
});

function exportGridCsv(Project $project): array
{
    test()->mock(JqGridEncoder::class)
        ->shouldReceive('encodeGridExportData')
        ->andReturn(Subject::where('project_id', $project->id)->cursor());

    app()->call([new GridExportCsvJob(User::factory()->create(), ['projectId' => $project->id, 'expeditionId' => null]), 'handle']);

    $file = Storage::disk('s3')->files(config('config.report_dir'))[0];

    return array_map('str_getcsv', array_filter(explode("\n", Storage::disk('s3')->get($file))));
}

it('writes each subject id under the _id column', function () {
    Subject::create([
        'project_id' => $this->project->id,
        'imageId' => 'image-1',
        'expedition_ids' => [21],
        'exported' => true,
        'accessURI' => 'https://example.org/image-1.jpg',
        'ocr' => '',
        'occurrence' => ['id' => 'occurrence-1'],
    ]);

    [$header, $row] = exportGridCsv($this->project);
    $record = array_combine($header, $row);

    expect($header[0])->toBe('_id')
        ->and($record['_id'])->toBe((string) Subject::first()->id)
        ->and($record['imageId'])->toBe('image-1')
        ->and($record['accessURI'])->toBe('https://example.org/image-1.jpg');
});

it('leaves out subject fields that are not in the header', function () {
    Subject::create([
        'project_id' => $this->project->id,
        'imageId' => 'image-1',
        'expedition_ids' => [21],
        'accessURI' => 'https://example.org/image-1.jpg',
        'legacyField' => 'not-in-header',
        'ocr' => '',
        'occurrence' => ['id' => 'occurrence-1'],
    ]);

    [$header, $row] = exportGridCsv($this->project);

    expect($row)->toHaveCount(count($header))
        ->and($row)->not->toContain('not-in-header');
});
