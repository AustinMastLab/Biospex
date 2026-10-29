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

use App\Jobs\EventTranscriptionExportCsvJob;
use App\Models\Event;
use App\Models\EventTeam;
use App\Models\EventTranscription;
use App\Models\EventUser;
use App\Models\PanoptesTranscription;
use App\Models\User;
use App\Services\Process\CreateReportService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    PanoptesTranscription::truncate();
    Notification::fake();
});

afterEach(function () {
    PanoptesTranscription::truncate();
});

it('leaves the transcription id out of the event transcription export', function () {
    $event = Event::factory()->create();
    EventTranscription::create([
        'classification_id' => 123,
        'event_id' => $event->id,
        'team_id' => EventTeam::factory()->create(['event_id' => $event->id])->id,
        'user_id' => EventUser::factory()->create()->id,
    ]);
    PanoptesTranscription::create(['classification_id' => 123, 'user_name' => 'volunteer']);

    $rows = null;
    $this->mock(CreateReportService::class)
        ->shouldReceive('createCsvReport')
        ->once()
        ->withArgs(function ($csvName, $data) use (&$rows) {
            $rows = $data;

            return true;
        })
        ->andReturn(null);

    app()->call([new EventTranscriptionExportCsvJob(User::factory()->create(), $event), 'handle']);

    expect($rows)->toHaveCount(1)
        ->and(array_keys($rows[0]))->toContain('classification_id', 'user_name')
        ->not->toContain('id')
        ->not->toContain('_id');
});
