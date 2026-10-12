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

use App\Jobs\ZooniverseClassificationCountJob;
use App\Jobs\ZooniversePusherJob;
use App\Jobs\ZooniverseTranscriptionJob;
use App\Models\Download;
use App\Models\Expedition;
use App\Services\Reconcile\ReconcileProcessAll;
use Illuminate\Support\Facades\Bus;

it('imports the transcripts, then updates the Pusher records, then the classification counts', function () {
    Bus::fake();
    $expedition = Expedition::factory()->create();

    app(ReconcileProcessAll::class)->process($expedition);

    Bus::assertChained([
        ZooniverseTranscriptionJob::class,
        ZooniversePusherJob::class,
        ZooniverseClassificationCountJob::class,
    ]);
});

it('records a download for each reconciled file type', function () {
    Bus::fake();
    $expedition = Expedition::factory()->create();

    app(ReconcileProcessAll::class)->process($expedition);
    app(ReconcileProcessAll::class)->process($expedition);

    $downloads = Download::where('expedition_id', $expedition->id)->get();

    expect($downloads)->toHaveCount(count(config('zooniverse.file_types')))
        ->and($downloads->pluck('type')->sort()->values()->all())->toBe(collect(config('zooniverse.file_types'))->sort()->values()->all())
        ->and($downloads->firstWhere('type', 'summary')?->file)->toBe("{$expedition->id}.html");
});
