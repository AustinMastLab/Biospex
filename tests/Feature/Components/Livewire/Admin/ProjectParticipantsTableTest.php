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

use App\Facades\CountHelper;
use App\Livewire\Admin\ProjectParticipantsTable;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

/**
 * Stand in for the project's cached participant counts.
 *
 * @param  array<int, array{user_name: string, transcriptionCount: int, expeditionCount: int, last_date: int}>  $participants
 */
function fakeParticipants(Project $project, array $participants): void
{
    CountHelper::shouldReceive('getTranscribersTranscriptionCount')
        ->with($project->id)
        ->andReturn(collect($participants));
}

/**
 * A signed-in user whose profile uses the given time zone.
 */
function signInWithTimezone(string $timezone): void
{
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id, 'timezone' => $timezone]);
    test()->actingAs($user);
}

it('lists participants with the most digitizations first', function () {
    signInWithTimezone('UTC');
    $project = Project::factory()->create();
    fakeParticipants($project, [
        ['user_name' => 'fern_fan', 'transcriptionCount' => 3, 'expeditionCount' => 1, 'last_date' => 1704067200],
        ['user_name' => 'moss_maven', 'transcriptionCount' => 40, 'expeditionCount' => 5, 'last_date' => 1704067200],
    ]);

    Livewire::test(ProjectParticipantsTable::class, ['project' => $project])
        ->assertSeeInOrder(['moss_maven', '5', '40', 'fern_fan', '1', '3'])
        ->assertSee('Showing 1 to 2 of 2 entries');
});

it('shows the last date in the signed-in user\'s time zone', function () {
    signInWithTimezone('America/New_York');
    $project = Project::factory()->create();
    // 2024-01-01 02:00 UTC is still 2023-12-31 in New York.
    fakeParticipants($project, [
        ['user_name' => 'fern_fan', 'transcriptionCount' => 3, 'expeditionCount' => 1, 'last_date' => 1704074400],
    ]);

    Livewire::test(ProjectParticipantsTable::class, ['project' => $project])
        ->assertSee('2023-12-31')
        ->assertDontSee('2024-01-01');
});

it('searches participant names', function () {
    signInWithTimezone('UTC');
    $project = Project::factory()->create();
    fakeParticipants($project, [
        ['user_name' => 'fern_fan', 'transcriptionCount' => 3, 'expeditionCount' => 1, 'last_date' => 1704067200],
        ['user_name' => 'moss_maven', 'transcriptionCount' => 40, 'expeditionCount' => 5, 'last_date' => 1704067200],
    ]);

    Livewire::test(ProjectParticipantsTable::class, ['project' => $project])
        ->set('search', 'FERN')
        ->assertSee('fern_fan')
        ->assertDontSee('moss_maven');
});

it('sorts by last date', function () {
    signInWithTimezone('UTC');
    $project = Project::factory()->create();
    fakeParticipants($project, [
        ['user_name' => 'recent_user', 'transcriptionCount' => 3, 'expeditionCount' => 1, 'last_date' => 1735689600],
        ['user_name' => 'early_user', 'transcriptionCount' => 40, 'expeditionCount' => 5, 'last_date' => 1577836800],
    ]);

    Livewire::test(ProjectParticipantsTable::class, ['project' => $project])
        ->call('sortBy', 'last_date')
        ->assertSeeInOrder(['early_user', '2020-01-01', 'recent_user', '2025-01-01']);
});

it('renders only the current page of a large project', function () {
    signInWithTimezone('UTC');
    $project = Project::factory()->create();
    fakeParticipants($project, collect(range(1, 30))->map(fn (int $number) => [
        'user_name' => sprintf('participant_%02d', $number),
        'transcriptionCount' => 100 - $number,
        'expeditionCount' => 1,
        'last_date' => 1704067200,
    ])->all());

    Livewire::test(ProjectParticipantsTable::class, ['project' => $project])
        ->assertSee('participant_10')
        ->assertDontSee('participant_11')
        ->assertSee('Showing 1 to 10 of 30 entries');
});
