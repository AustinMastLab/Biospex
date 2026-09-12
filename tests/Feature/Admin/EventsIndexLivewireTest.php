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

use App\Livewire\Admin\EventsIndex;
use App\Models\Event;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::forget('public_sort:events:version');
});

it('admin user sees all events and sorting toggles by title', function () {
    $g1 = Group::factory()->create(['title' => 'G1']);
    $g2 = Group::factory()->create(['title' => 'G2']);

    $p1 = Project::factory()->for($g1)->create(['title' => 'Alpha Project']);
    $p2 = Project::factory()->for($g2)->create(['title' => 'Zebra Project']);

    Event::factory()->for($p1)->create(['title' => 'Alpha', 'start_date' => now()->addDay(), 'end_date' => now()->addDays(2)]);
    Event::factory()->for($p2)->create(['title' => 'Zebra', 'start_date' => now()->addDays(2), 'end_date' => now()->addDays(3)]);

    $admin = User::factory()->create();
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));
    $admin->assignGroup($g1);
    $admin->assignGroup($g2);

    $this->actingAs($admin);

    Livewire::test(EventsIndex::class)
        ->call('sortBy', 'title')
        ->assertSet('sort', 'title')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Alpha', 'Zebra'])
        ->call('sortBy', 'title')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['Zebra', 'Alpha']);
});

it('non-admin user sees only scoped subset', function () {
    $g1 = Group::factory()->create(['title' => 'G1']);
    $g2 = Group::factory()->create(['title' => 'G2']);

    $p1 = Project::factory()->for($g1)->create(['title' => 'Alpha Project']);
    $p2 = Project::factory()->for($g2)->create(['title' => 'Zebra Project']);

    $user = User::factory()->create();
    $user->assignGroup($g1);

    Event::factory()->for($p1)->create(['title' => 'E1', 'owner_id' => $user->id, 'start_date' => now()->addDay(), 'end_date' => now()->addDays(2)]);
    Event::factory()->for($p2)->create(['title' => 'E2', 'start_date' => now()->addDays(2), 'end_date' => now()->addDays(3)]);

    $this->actingAs($user);

    Livewire::test(EventsIndex::class)
        ->assertSee('E1')
        ->assertDontSee('E2');
});

it('loads nine events initially and appends the final page for admins', function () {
    $admin = User::factory()->create();
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));
    $project = Project::factory()->create();

    foreach (range(1, 10) as $number) {
        Event::factory()->for($project)->create([
            'title' => sprintf('Event %02d', $number),
            'start_date' => now()->addDays($number),
            'end_date' => now()->addDays($number + 1),
        ]);
    }

    $this->actingAs($admin->fresh());

    Livewire::test(EventsIndex::class)
        ->assertSee('Event 01')
        ->assertSee('Event 09')
        ->assertDontSee('Event 10')
        ->assertSet('hasMore', true)
        ->call('loadMore')
        ->assertSee('Event 10')
        ->assertSet('hasMore', false);
});

it('loads completed events only after changing type', function () {
    $admin = User::factory()->create();
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));
    $project = Project::factory()->create();
    Event::factory()->for($project)->create(['title' => 'Active Event', 'start_date' => now()->subDay(), 'end_date' => now()->addDay()]);
    Event::factory()->for($project)->create(['title' => 'Completed Event', 'start_date' => now()->subDays(2), 'end_date' => now()->subDay()]);

    $this->actingAs($admin->fresh());

    Livewire::test(EventsIndex::class)
        ->assertSee('Active Event')
        ->assertDontSee('Completed Event')
        ->call('setType', 'completed')
        ->assertSee('Completed Event')
        ->assertDontSee('Active Event');
});
