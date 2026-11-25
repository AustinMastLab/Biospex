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

use App\Models\Reconcile;
use App\Services\Csv\AwsS3CsvService;
use App\Services\Reconcile\ExpertReconcileService;
use App\Services\Subject\SubjectService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Reconcile::truncate();
});

afterEach(function () {
    Reconcile::truncate();
});

it('migrates each subject\'s reconciled row once, skipping subjects already stored', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put(config('zooniverse.directory.reconciled').'/12.csv', 'subject_id');
    Reconcile::create(['subject_id' => 101, 'subject_expeditionId' => 12]);

    // The rows as read from the reconciled CSV: every value is text.
    $service = Mockery::mock(ExpertReconcileService::class, [app(Reconcile::class), app(SubjectService::class), app(AwsS3CsvService::class)])
        ->makePartial();
    $service->shouldReceive('getCsvRows')->andReturn(collect([
        ['subject_id' => '101', 'subject_expeditionId' => '12'],
        ['subject_id' => '102', 'subject_expeditionId' => '12'],
    ]));

    $service->migrateReconcileCsv('12');

    expect(Reconcile::where('subject_id', 101)->count())->toBe(1)
        ->and(Reconcile::where('subject_id', 102)->count())->toBe(1);
});
