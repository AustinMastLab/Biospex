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

use App\Models\Expedition;
use App\Models\Reconcile;
use App\Services\Api\AwsS3ApiService;
use App\Services\Reconcile\ExpertReconcilePublishService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Reconcile::truncate();
    Notification::fake();
    config(['filesystems.disks.s3.bucket' => 'test-bucket']);
    Storage::fake('s3');
    Storage::disk('s3')->makeDirectory(config('zooniverse.directory.reconciled-with-expert'));

    // The CSV is streamed to S3; write it to the faked disk instead.
    $this->mock(AwsS3ApiService::class)
        ->shouldReceive('createS3BucketStream')
        ->andReturnUsing(fn ($bucket, $path, $mode) => fopen(Storage::disk('s3')->path($path), $mode));
});

afterEach(function () {
    Reconcile::truncate();
});

it('leaves the record id out of the published expert reconcile csv', function () {
    $expedition = Expedition::factory()->create();
    Reconcile::create(['subject_expeditionId' => $expedition->id, 'subject_id' => 'subject-1', 'subject_problem' => 0]);

    app(ExpertReconcilePublishService::class)->publishReconciled($expedition);

    $csv = Storage::disk('s3')->get(config('zooniverse.directory.reconciled-with-expert').'/'.$expedition->id.'.csv');
    [$header, $row] = array_map('str_getcsv', array_values(array_filter(explode("\n", $csv))));

    expect($header)->toContain('subject_id')
        ->not->toContain('id')
        ->not->toContain('_id')
        ->and($row)->toHaveCount(count($header));
});
