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

use App\Services\Api\AwsS3ApiService;
use App\Services\Process\CreateReportService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['filesystems.disks.s3.bucket' => 'test-bucket']);
    Storage::fake('s3');
    Storage::disk('s3')->makeDirectory(config('config.report_dir'));

    $this->mock(AwsS3ApiService::class)
        ->shouldReceive('createS3BucketStream')
        ->andReturnUsing(fn ($bucket, $path, $mode) => fopen(Storage::disk('s3')->path($path), $mode));
});

function reportLines(string $csvName): array
{
    $csv = Storage::disk('s3')->get(config('config.report_dir').'/'.$csvName);

    return array_map('str_getcsv', array_values(array_filter(explode("\n", $csv))));
}

it('builds the report header from every row, not just the first', function () {
    // Like import rejection reports: some rows carry the full record, some only a reason.
    app(CreateReportService::class)->createCsvReport('report.csv', [
        ['Reason' => 'Database insert failed'],
        ['Reason' => 'Duplicate imageId', 'imageId' => 'image-2', 'accessURI' => 'https://example.org/2.jpg'],
    ]);

    expect(reportLines('report.csv'))->toBe([
        ['Reason', 'imageId', 'accessURI'],
        ['Database insert failed', '', ''],
        ['Duplicate imageId', 'image-2', 'https://example.org/2.jpg'],
    ]);
});

it('writes a report whose rows do not start at key zero', function () {
    app(CreateReportService::class)->createCsvReport('report.csv', [3 => ['user' => 'volunteer'], 7 => ['user' => 'expert']]);

    expect(reportLines('report.csv'))->toBe([['user'], ['volunteer'], ['expert']]);
});
