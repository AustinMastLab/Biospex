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

use App\Livewire\Admin\ExpeditionsIndex;
use App\Models\Expedition;
use App\Models\ExpeditionStat;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('s3');
});

function makeAdminExpedition(Project $project, array $attributes = []): Expedition
{
    $expedition = Expedition::factory()->for($project)->create($attributes);
    ExpeditionStat::factory()->for($expedition, 'expedition')->create();

    return $expedition;
}

it('admin user sees all expeditions and sorting toggles by title', function () {
    $g1 = Group::factory()->create(['title' => 'G1']);
    $g2 = Group::factory()->create(['title' => 'G2']);

    $p1 = Project::factory()->for($g1)->create(['title' => 'Alpha Project']);
    $p2 = Project::factory()->for($g2)->create(['title' => 'Zebra Project']);

    makeAdminExpedition($p1, ['title' => 'Alpha', 'completed' => 0]);
    makeAdminExpedition($p2, ['title' => 'Zebra', 'completed' => 0]);

    $admin = User::factory()->create();
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));

    $this->actingAs($admin->fresh());

    Livewire::test(ExpeditionsIndex::class)
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

    makeAdminExpedition($p1, ['title' => 'E1', 'completed' => 0]);
    makeAdminExpedition($p2, ['title' => 'E2', 'completed' => 0]);

    $user = User::factory()->create();
    $user->assignGroup($g1);

    $this->actingAs($user->fresh());

    Livewire::test(ExpeditionsIndex::class)
        ->assertSee('E1')
        ->assertDontSee('E2');
});

it('loads no more than twelve expeditions initially and appends the next page', function () {
    $group = Group::factory()->create();
    $project = Project::factory()->for($group)->create();
    $admin = User::factory()->create();
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));

    foreach (range(1, 13) as $number) {
        makeAdminExpedition($project, [
            'title' => sprintf('Expedition %02d', $number),
            'created_at' => now()->addSeconds($number),
            'completed' => 0,
        ]);
    }

    $this->actingAs($admin->fresh());

    Livewire::test(ExpeditionsIndex::class)
        ->assertSee('Expedition 01')
        ->assertSee('Expedition 12')
        ->assertDontSee('Expedition 13')
        ->assertSet('hasMore', true)
        ->call('loadMore')
        ->assertSeeInOrder(['Expedition 01', 'Expedition 12', 'Expedition 13'])
        ->assertSet('page', 2)
        ->assertSet('hasMore', false);
});

it('loads completed expeditions only when an admin changes type', function () {
    $group = Group::factory()->create();
    $project = Project::factory()->for($group)->create();
    $admin = User::factory()->create();
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));
    makeAdminExpedition($project, ['title' => 'Active Expedition', 'completed' => 0]);
    makeAdminExpedition($project, ['title' => 'Completed Expedition', 'completed' => 1]);

    $this->actingAs($admin->fresh());

    Livewire::test(ExpeditionsIndex::class)
        ->assertSee('Active Expedition')
        ->assertDontSee('Completed Expedition')
        ->call('setType', 'completed')
        ->assertSee('Completed Expedition')
        ->assertDontSee('Active Expedition')
        ->assertSet('type', 'completed');
});

it('sorts admin expeditions by project and resets the loaded page', function () {
    $admin = User::factory()->create();
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));
    $firstProject = Project::factory()->create(['title' => 'Alpha Project']);
    $secondProject = Project::factory()->create(['title' => 'Zebra Project']);

    makeAdminExpedition($secondProject, ['title' => 'Alpha Expedition', 'completed' => 0]);
    makeAdminExpedition($firstProject, ['title' => 'Zebra Expedition', 'completed' => 0]);

    $this->actingAs($admin->fresh());

    Livewire::test(ExpeditionsIndex::class)
        ->call('sortBy', 'project')
        ->assertSet('sort', 'project')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Zebra Expedition', 'Alpha Expedition'])
        ->call('sortBy', 'project')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['Alpha Expedition', 'Zebra Expedition'])
        ->assertSet('page', 1);
});
