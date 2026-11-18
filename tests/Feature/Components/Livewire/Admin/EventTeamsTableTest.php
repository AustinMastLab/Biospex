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

use App\Livewire\Admin\EventTeamsTable;
use App\Models\Event;
use App\Models\EventTeam;
use App\Models\EventUser;
use Database\Factories\EventTranscriptionFactory;
use Livewire\Livewire;

/**
 * A team member of the event with the given number of digitizations in it.
 */
function eventTeamMember(Event $event, EventTeam $team, string $name, int $digitizations = 0): EventUser
{
    $user = EventUser::factory()->create(['nfn_user' => $name]);
    $team->users()->attach($user);
    EventTranscriptionFactory::new()->count($digitizations)->create([
        'event_id' => $event->id,
        'team_id' => $team->id,
        'user_id' => $user->id,
    ]);

    return $user;
}

it('lists each team member with their digitizations in the event only', function () {
    $event = Event::factory()->create();
    $team = EventTeam::factory()->create(['event_id' => $event->id, 'title' => 'Plant Pals']);
    $member = eventTeamMember($event, $team, 'fern_fan', 3);
    EventTranscriptionFactory::new()->create(['user_id' => $member->id]);

    Livewire::test(EventTeamsTable::class, ['event' => $event])
        ->assertSeeInOrder(['Plant Pals', 'fern_fan', '3'])
        ->assertSee('Showing 1 to 1 of 1 entries');
});

it('leaves out the teams of other events', function () {
    $event = Event::factory()->create();
    eventTeamMember($event, EventTeam::factory()->create(['event_id' => $event->id]), 'fern_fan');
    $otherEvent = Event::factory()->create();
    eventTeamMember($otherEvent, EventTeam::factory()->create(['event_id' => $otherEvent->id]), 'moss_maven');

    Livewire::test(EventTeamsTable::class, ['event' => $event])
        ->assertSee('fern_fan')
        ->assertDontSee('moss_maven');
});

it('searches team and user names', function () {
    $event = Event::factory()->create();
    $pals = EventTeam::factory()->create(['event_id' => $event->id, 'title' => 'Plant Pals']);
    $thyme = EventTeam::factory()->create(['event_id' => $event->id, 'title' => 'Thyme After Thyme']);
    eventTeamMember($event, $pals, 'fern_fan');
    eventTeamMember($event, $thyme, 'moss_maven');
    eventTeamMember($event, $thyme, 'oak_lover');

    Livewire::test(EventTeamsTable::class, ['event' => $event])
        ->set('search', 'pals')
        ->assertSee('fern_fan')
        ->assertDontSee('moss_maven')
        ->set('search', 'oak')
        ->assertSee('oak_lover')
        ->assertDontSee('fern_fan');
});

it('sorts by digitizations, most first on the second click', function () {
    $event = Event::factory()->create();
    $team = EventTeam::factory()->create(['event_id' => $event->id]);
    eventTeamMember($event, $team, 'fern_fan', 1);
    eventTeamMember($event, $team, 'moss_maven', 4);
    eventTeamMember($event, $team, 'oak_lover', 2);

    Livewire::test(EventTeamsTable::class, ['event' => $event])
        ->call('sortBy', 'transcriptions_count')
        ->assertSeeInOrder(['fern_fan', 'oak_lover', 'moss_maven'])
        ->call('sortBy', 'transcriptions_count')
        ->assertSeeInOrder(['moss_maven', 'oak_lover', 'fern_fan']);
});

it('shows ten members a page and moves to the next page', function () {
    $event = Event::factory()->create();
    $team = EventTeam::factory()->create(['event_id' => $event->id]);
    foreach (range(1, 12) as $number) {
        eventTeamMember($event, $team, sprintf('member_%02d', $number));
    }

    Livewire::test(EventTeamsTable::class, ['event' => $event])
        ->assertSee('member_10')
        ->assertDontSee('member_11')
        ->call('nextPage', 'teamsPage')
        ->assertSee('member_11')
        ->assertSee('Showing 11 to 12 of 12 entries');
});

it('ignores a column it cannot sort by', function () {
    $event = Event::factory()->create();

    Livewire::test(EventTeamsTable::class, ['event' => $event])
        ->call('sortBy', 'event_users.id; drop table events')
        ->assertSet('sortField', 'team');
});
