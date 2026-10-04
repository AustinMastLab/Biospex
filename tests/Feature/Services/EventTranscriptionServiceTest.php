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

use App\Models\Event;
use App\Models\EventTeam;
use App\Models\EventTranscription;
use App\Models\EventUser;
use App\Services\Event\EventTranscriptionService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function () {
    $this->event = Event::factory()->create([
        'start_date' => Carbon::parse('2026-10-07 11:00:00', 'UTC'),
        'end_date' => Carbon::parse('2026-10-08 11:00:00', 'UTC'),
    ]);
    $this->team = EventTeam::factory()->create(['event_id' => $this->event->id]);
    $this->user = EventUser::factory()->create(['nfn_user' => 'volunteer']);
    $this->team->users()->attach($this->user);
});

function recordEventTranscription(Event $event, int $classificationId = 778691035): bool
{
    return app(EventTranscriptionService::class)->createEventTranscription(
        $classificationId,
        $event->project_id,
        'volunteer',
        Carbon::parse('2026-10-07 12:00:00', 'UTC'),
    );
}

it('records a classification once for each event team the user belongs to', function () {
    expect(recordEventTranscription($this->event))->toBeTrue();

    expect(EventTranscription::where('classification_id', 778691035)->sole())
        ->event_id->toBe($this->event->id)
        ->team_id->toBe($this->team->id)
        ->user_id->toBe($this->user->id);
});

it('does not duplicate a classification that is recorded twice', function () {
    recordEventTranscription($this->event);
    recordEventTranscription($this->event);

    expect(EventTranscription::where('classification_id', 778691035)->count())->toBe(1);
});

it('rejects a duplicate row at the database level', function () {
    $attributes = [
        'classification_id' => 778691035,
        'event_id' => $this->event->id,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ];
    EventTranscription::create($attributes);

    expect(fn () => EventTranscription::create($attributes))->toThrow(UniqueConstraintViolationException::class);
});
