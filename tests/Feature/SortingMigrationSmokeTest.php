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

use App\Models\Group;
use App\Models\PanoptesProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
    config(['filesystems.disks.s3.bucket' => 'test-bucket']);

    // Ensure "admin-ness" matches what User::isAdmin() checks.
    config(['config.admin.group' => 'Admin']);
});

it('renders public projects page', function () {
    $project = Project::factory()->create(['title' => 'Smoke Test Project']);
    PanoptesProject::factory()->create(['project_id' => $project->id]);

    $this->get(route('front.projects.index'))
        ->assertStatus(200)
        ->assertSee('Biospex Projects')
        ->assertSeeLivewire('front.projects-index');
});

it('renders public events page', function () {
    $this->get(route('front.events.index'))
        ->assertStatus(200)
        ->assertSee('Biospex Events')
        ->assertSeeLivewire('front.events-index');
});

it('renders public expeditions page', function () {
    $this->get(route('front.expeditions.index'))
        ->assertStatus(200)
        ->assertSee('Biospex Expeditions')
        ->assertSeeLivewire('front.expeditions-index');
});

it('renders public project show page', function () {
    $project = Project::factory()->create(['title' => 'Show Project']);
    PanoptesProject::factory()->create(['project_id' => $project->id]);

    $this->get(route('front.projects.show', $project->slug))
        ->assertStatus(200)
        ->assertSee('Show Project')
        ->assertSeeLivewire('front.events-index')
        ->assertSeeLivewire('front.expeditions-index');
});

it('renders admin projects index', function () {
    $adminGroup = Group::where('title', 'Admin')->first() ?? Group::factory()->create(['title' => 'Admin']);
    $admin = User::factory()->verified()->create();
    $admin->groups()->attach($adminGroup);

    $this->actingAs($admin)
        ->get(route('admin.projects.index'))
        ->assertStatus(200)
        ->assertSee('Biospex Projects')
        ->assertSeeLivewire('admin.projects-index');
});

it('renders admin events index', function () {
    $adminGroup = Group::where('title', 'Admin')->first() ?? Group::factory()->create(['title' => 'Admin']);
    $admin = User::factory()->verified()->create();
    $admin->groups()->attach($adminGroup);

    $this->actingAs($admin)
        ->get(route('admin.events.index'))
        ->assertStatus(200)
        ->assertSee('Biospex Events')
        ->assertSeeLivewire('admin.events-index');
});

it('renders admin expeditions index', function () {
    $adminGroup = Group::where('title', 'Admin')->first() ?? Group::factory()->create(['title' => 'Admin']);
    $admin = User::factory()->verified()->create();
    $admin->groups()->attach($adminGroup);

    $this->actingAs($admin)
        ->get(route('admin.expeditions.index'))
        ->assertStatus(200)
        ->assertSee('Biospex Expeditions')
        ->assertSeeLivewire('admin.expeditions-index');
});

it('renders admin project show page with Livewire', function () {
    $adminGroup = Group::where('title', 'Admin')->first() ?? Group::factory()->create(['title' => 'Admin']);
    $admin = User::factory()->verified()->create();
    $admin->groups()->attach($adminGroup);
    $project = Project::factory()->create(['title' => 'Admin Show Project']);

    $this->actingAs($admin)
        ->get(route('admin.projects.show', $project))
        ->assertStatus(200)
        ->assertSee('Admin Show Project')
        ->assertSeeLivewire('admin.expeditions-index');
});
