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

use App\Livewire\Admin\ProjectsIndex;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('s3');
});

function makeAdminUserWithGroups(array $groups): User
{
    $admin = User::factory()->create();
    // Mark as admin by assigning Admin group title and attach all provided groups too
    $adminGroup = Group::factory()->create(['title' => config('config.admin.group')]);
    $admin->assignGroup($adminGroup);
    foreach ($groups as $g) {
        $admin->assignGroup($g);
    }

    return $admin;
}

function makeUserInGroup(Group $group): User
{
    $user = User::factory()->create();
    $user->assignGroup($group);

    return $user;
}

it('admin user sees all projects', function () {
    $g1 = Group::factory()->create(['title' => 'G1']);
    $g2 = Group::factory()->create(['title' => 'G2']);
    $g3 = Group::factory()->create(['title' => 'G3']);

    $p1 = Project::factory()->for($g1)->create(['title' => 'Alpha']);
    $p2 = Project::factory()->for($g2)->create(['title' => 'Zebra']);
    $p3 = Project::factory()->for($g3)->create(['title' => 'Unassociated']);

    $admin = makeAdminUserWithGroups([$g1, $g2]);

    $this->actingAs($admin);

    Livewire::test(ProjectsIndex::class)
        ->assertSee('Alpha')
        ->assertSee('Zebra')
        ->assertSee('Unassociated');
});

it('non-admin user sees only scoped subset', function () {
    $g1 = Group::factory()->create(['title' => 'G1']);
    $g2 = Group::factory()->create(['title' => 'G2']);

    $p1 = Project::factory()->for($g1)->create(['title' => 'Alpha']);
    $p2 = Project::factory()->for($g2)->create(['title' => 'Zebra']);

    $user = makeUserInGroup($g1);
    $this->actingAs($user);

    Livewire::test(ProjectsIndex::class)
        ->assertSee('Alpha')
        ->assertDontSee('Zebra');
});

it('sorting toggles order on same field and resets on switch', function () {
    $g = Group::factory()->create(['title' => 'G']);
    Project::factory()->for($g)->create(['title' => 'Alpha']);
    Project::factory()->for($g)->create(['title' => 'Zebra']);

    $admin = makeAdminUserWithGroups([$g]);
    $this->actingAs($admin);

    Livewire::test(ProjectsIndex::class)
        ->call('sortBy', 'title')
        ->assertSet('sort', 'title')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Alpha', 'Zebra'])
        ->call('sortBy', 'title')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['Zebra', 'Alpha'])
        ->call('sortBy', 'date')
        ->assertSet('sort', 'date')
        ->assertSet('order', 'asc');
});

it('loads another authorization-scoped page of projects without duplicates', function () {
    $group = Group::factory()->create(['title' => 'G']);
    $projects = Project::factory()->for($group)->count(10)->sequence(
        fn ($sequence) => ['title' => sprintf('Project %02d', $sequence->index + 1)],
    )->create();
    $admin = makeAdminUserWithGroups([$group]);

    $this->actingAs($admin);

    Livewire::test(ProjectsIndex::class)
        ->assertSee('Project 01')
        ->assertDontSee('Project 10')
        ->assertSet('hasMore', true)
        ->call('loadMore')
        ->assertSee('Project 01')
        ->assertSee('Project 10')
        ->assertSet('hasMore', false);
});
