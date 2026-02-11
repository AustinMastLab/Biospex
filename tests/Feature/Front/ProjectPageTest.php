<?php

/*
 * Copyright (C) 2015  Biospex
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

use Database\Seeders\ProjectPageTestSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

it('has projectpage page', function () {
    $response = $this->get(route('front.projects.index'));

    $response->assertStatus(200);
});

it('returns correct view', function () {
    $response = $this->get(route('front.projects.index'));

    $response->assertViewIs('front.project.index');
});

it('shows list of projects', function () {
    $this->seed(ProjectPageTestSeeder::class);

    $projects = \App\Models\Project::all();
    $this->assertCount(10, $projects->toArray());

    $titles = $projects->pluck('title')->toArray();

    $response = $this->get(route('front.projects.index'));

    foreach ($titles as $title) {
        $response->assertSee($title);
    }
});

// Legacy /sort endpoint tests were removed in Task 9.
// Sorting behavior is covered by Livewire tests (Front ProjectsIndex component).
