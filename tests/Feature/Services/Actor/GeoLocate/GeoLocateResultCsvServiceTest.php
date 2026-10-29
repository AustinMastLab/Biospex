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

use App\Models\ActorExpedition;
use App\Models\GeoLocateExport;
use App\Services\Actor\GeoLocate\GeoLocateResultCsvService;
use App\Services\Api\AwsS3ApiService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    GeoLocateExport::truncate();
    config(['filesystems.disks.s3.bucket' => 'test-bucket']);
    Storage::fake('s3');
    Storage::disk('s3')->makeDirectory(config('geolocate.dir.geo-reconciled'));

    $this->mock(AwsS3ApiService::class)
        ->shouldReceive('createS3BucketStream')
        ->andReturnUsing(fn ($bucket, $path, $mode) => fopen(Storage::disk('s3')->path($path), $mode));
});

afterEach(function () {
    GeoLocateExport::truncate();
});

it('gives a field missing from the first record its own column in the geo reconciled csv', function () {
    GeoLocateExport::create(['subject_expeditionId' => 9, 'subject_id' => 1, 'locality' => 'Tallahassee']);
    GeoLocateExport::create(['subject_expeditionId' => 9, 'subject_id' => 2, 'locality' => 'Miami', 'habitat' => 'Swamp']);
    $first = GeoLocateExport::where('subject_id', 1)->first();
    $second = GeoLocateExport::where('subject_id', 2)->first();

    Storage::disk('s3')->put(config('geolocate.dir.csv').'/result.csv', implode("\n", [
        'CatalogNumber,Latitude,Longitude',
        "{$first->id},30.44,-84.28",
        "{$second->id},25.76,-80.19",
    ]));

    $service = app(GeoLocateResultCsvService::class);
    $service->sourceFile = config('geolocate.dir.csv').'/result.csv';
    $actorExpedition = new ActorExpedition;
    $actorExpedition->expedition_id = 9;
    $service->setDestinationFile($actorExpedition);

    $service->processCsvDownload([]);
    $service->createUpdateGeoReconciledDownload(9);

    $csv = Storage::disk('s3')->get(config('geolocate.dir.geo-reconciled').'/9.csv');
    $lines = array_map('str_getcsv', array_values(array_filter(explode("\n", $csv))));
    $header = array_shift($lines);
    $records = array_map(fn ($line) => array_combine($header, $line), $lines);

    expect($header)->toContain('locality', 'habitat', 'Latitude', 'Longitude')
        ->not->toContain('CatalogNumber')
        ->and($records)->toHaveCount(2)
        ->and($records[0]['habitat'])->toBe('')
        ->and($records[1]['habitat'])->toBe('Swamp')
        ->and($records[1]['Latitude'])->toBe('25.76');
});
