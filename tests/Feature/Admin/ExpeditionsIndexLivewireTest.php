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

it('admin user sees all expeditions and sorting toggles by title', function () {
    $g1 = Group::factory()->create(['title' => 'G1']);
    $g2 = Group::factory()->create(['title' => 'G2']);

    $p1 = Project::factory()->for($g1)->create(['title' => 'Alpha Project']);
    $p2 = Project::factory()->for($g2)->create(['title' => 'Zebra Project']);

    $e1 = Expedition::factory()->for($p1)->create(['title' => 'Alpha', 'completed' => 0]);
    $e2 = Expedition::factory()->for($p2)->create(['title' => 'Zebra', 'completed' => 0]);
    ExpeditionStat::factory()->for($e1, 'expedition')->create();
    ExpeditionStat::factory()->for($e2, 'expedition')->create();

    $admin = User::factory()->create();
    $admin->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));
    $admin->assignGroup($g1);
    $admin->assignGroup($g2);

    $this->actingAs($admin);

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

    $e1 = Expedition::factory()->for($p1)->create(['title' => 'E1', 'completed' => 0]);
    $e2 = Expedition::factory()->for($p2)->create(['title' => 'E2', 'completed' => 0]);
    ExpeditionStat::factory()->for($e1, 'expedition')->create();
    ExpeditionStat::factory()->for($e2, 'expedition')->create();

    $user = User::factory()->create();
    $user->assignGroup($g1);

    $this->actingAs($user);

    Livewire::test(ExpeditionsIndex::class)
        ->assertSee('E1')
        ->assertDontSee('E2');
});
