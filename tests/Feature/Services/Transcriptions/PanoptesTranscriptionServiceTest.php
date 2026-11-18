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

use App\Models\PanoptesTranscription;
use App\Services\Transcriptions\PanoptesTranscriptionService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    PanoptesTranscription::truncate();
});

afterEach(function () {
    PanoptesTranscription::truncate();
});

it('summarises each participant of a project as plain values', function () {
    foreach ([[31, '2024-01-01 10:00:00'], [31, '2024-03-05 08:30:00'], [32, '2024-02-01 12:00:00']] as [$expeditionId, $finishedAt]) {
        PanoptesTranscription::create([
            'subject_projectId' => 7,
            'subject_expeditionId' => $expeditionId,
            'user_name' => 'fern_fan',
            'classification_finished_at' => Carbon::parse($finishedAt, 'UTC'),
        ]);
    }
    PanoptesTranscription::create([
        'subject_projectId' => 8,
        'subject_expeditionId' => 40,
        'user_name' => 'other_project_user',
        'classification_finished_at' => Carbon::parse('2024-01-01', 'UTC'),
    ]);

    $participants = app(PanoptesTranscriptionService::class)->getTranscribersTranscriptionCount(7);

    expect($participants->all())->toBe([[
        'user_name' => 'fern_fan',
        'transcriptionCount' => 3,
        'expeditionCount' => 2,
        'last_date' => Carbon::parse('2024-03-05 08:30:00', 'UTC')->getTimestamp(),
    ]]);
});
