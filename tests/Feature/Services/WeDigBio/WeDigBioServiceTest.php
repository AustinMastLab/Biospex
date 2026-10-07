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

use App\Models\Project;
use App\Models\WeDigBioEvent;
use App\Models\WeDigBioEventTranscription;
use App\Services\WeDigBio\WeDigBioService;

beforeEach(function () {
    $this->event = WeDigBioEvent::factory()->create([
        'active' => true,
        'start_date' => '2026-10-07 11:00:00',
        'end_date' => '2026-10-11 10:59:00',
    ]);
    $this->project = Project::factory()->create();
    addWeDigBioTranscription($this->event, $this->project);
});

afterEach(function () {
    $this->travelBack();
});

/**
 * Record a WeDigBio transcription for the event.
 */
function addWeDigBioTranscription(WeDigBioEvent $event, Project $project): void
{
    WeDigBioEventTranscription::create([
        'classification_id' => fake()->unique()->randomNumber(8),
        'project_id' => $project->id,
        'event_id' => $event->id,
    ]);
}

/**
 * Read the event's progress transcription count through the service.
 */
function weDigBioProgressCount(WeDigBioEvent $event): int
{
    return app(WeDigBioService::class)->getWeDigBioEventTranscriptions($event)->transcriptions_count;
}

it('keeps showing the cached progress within the same five-minute window', function () {
    $this->travelTo('2026-10-07 12:01:00 UTC');
    weDigBioProgressCount($this->event);
    addWeDigBioTranscription($this->event, $this->project);
    $this->travelTo('2026-10-07 12:04:59 UTC');

    $count = weDigBioProgressCount($this->event);

    expect($count)->toBe(1);
});

it('shows new transcriptions in progress once the next five-minute window starts', function () {
    $this->travelTo('2026-10-07 12:01:00 UTC');
    weDigBioProgressCount($this->event);
    addWeDigBioTranscription($this->event, $this->project);
    $this->travelTo('2026-10-07 12:05:00 UTC');

    $count = weDigBioProgressCount($this->event);

    expect($count)->toBe(2);
});
