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

use App\Livewire\EventScoreboard;
use App\Livewire\WeDigBioProgress;
use App\Models\Event;
use App\Models\EventTeam;
use App\Models\EventTranscription;
use App\Models\EventUser;
use App\Models\Project;
use App\Models\WeDigBioEvent;
use App\Models\WeDigBioEventTranscription;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
});

it('shows an event\'s teams by transcriptions when the scoreboard opens, and refreshes every five minutes', function () {
    $event = Event::factory()->create(['title' => 'Fern Week', 'start_date' => now()->subDay(), 'end_date' => now()->addDay()]);
    $ferns = EventTeam::factory()->for($event)->create(['title' => 'Fern Finders']);
    $mosses = EventTeam::factory()->for($event)->create(['title' => 'Moss Squad']);
    $user = EventUser::factory()->create();
    foreach ([[$ferns, 1], [$mosses, 2], [$mosses, 3], [$mosses, 4]] as [$team, $classification]) {
        EventTranscription::create(['classification_id' => $classification, 'event_id' => $event->id, 'team_id' => $team->id, 'user_id' => $user->id]);
    }

    Livewire::test(EventScoreboard::class)
        ->assertDontSee('Fern Week')
        ->call('open', $event->uuid)
        ->assertSee('Fern Week')
        ->assertSeeInOrder(['Moss Squad', '3', 'Fern Finders', '1'])
        ->assertSeeHtml('wire:poll.300s')
        ->assertSee('Scoreboard updates every 5 minutes.')
        ->call('close')
        ->assertDontSee('Fern Week')
        ->assertDontSeeHtml('wire:poll.300s');
});

it('says when the scoreboard\'s event is not found', function () {
    Livewire::test(EventScoreboard::class)
        ->call('open', 'no-such-event')
        ->assertSee(t('Error: Event not found.'));
});

it('shows the current WeDigBio event\'s transcriptions per project when the progress modal opens', function () {
    // The current event: the test database has no slug column (the live table, shared with WeDigBio Reports, does).
    $weDigBioEvent = WeDigBioEvent::factory()->create(['active' => 1]);
    $project = Project::factory()->create(['title' => 'Herbarium Ledgers']);
    foreach ([1, 2] as $classification) {
        WeDigBioEventTranscription::create(['classification_id' => $classification, 'event_id' => $weDigBioEvent->id, 'project_id' => $project->id]);
    }

    Livewire::test(WeDigBioProgress::class)
        ->assertDontSee('Herbarium Ledgers')
        ->call('open')
        ->assertSee('Herbarium Ledgers')
        ->assertSeeHtml('wire:poll.300s')
        ->call('close')
        ->assertDontSee('Herbarium Ledgers');
});

it('shows a past WeDigBio event\'s progress when opened by its slug, not the current event\'s', function () {
    $past = WeDigBioEvent::factory()->create(['slug' => 'wedigbio-2025-fall', 'active' => 0]);
    $current = WeDigBioEvent::factory()->create(['slug' => 'wedigbio-2026-fall', 'active' => 1]);
    $ledgers = Project::factory()->create(['title' => 'Herbarium Ledgers']);
    $ferns = Project::factory()->create(['title' => 'Fern Sheets']);
    WeDigBioEventTranscription::create(['classification_id' => 1, 'event_id' => $past->id, 'project_id' => $ledgers->id]);
    WeDigBioEventTranscription::create(['classification_id' => 2, 'event_id' => $current->id, 'project_id' => $ferns->id]);

    Livewire::test(WeDigBioProgress::class)
        ->call('open', 'wedigbio-2025-fall')
        ->assertSee('Herbarium Ledgers')
        ->assertDontSee('Fern Sheets');
});

it('says there is no current WeDigBio event when none is running', function () {
    Livewire::test(WeDigBioProgress::class)
        ->call('open')
        ->assertSee(t('No current WeDigBio Event'));
});
