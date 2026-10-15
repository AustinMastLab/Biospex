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

use App\Models\PanoptesProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('s3');
});

function createPublicApiProject(array $attributes = []): Project
{
    $project = Project::factory()->create($attributes);
    PanoptesProject::factory()->for($project)->create();

    return $project;
}

it('returns 401 when no token is provided', function () {
    $this->getJson(route('api.v1.projects.index'))->assertUnauthorized();
});

it('returns 403 when the token lacks the public:read ability', function () {
    Sanctum::actingAs(User::factory()->create(), ['wedigbio-dashboard:read']);

    $this->getJson(route('api.v1.projects.index'))->assertForbidden();
});

it('lists only projects running on Zooniverse', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);
    $public = createPublicApiProject();
    Project::factory()->create();

    $this->getJson(route('api.v1.projects.index'))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.uuid', (string) $public->uuid);
});

it('limits a page to 100 projects', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);

    $this->getJson(route('api.v1.projects.index', ['per_page' => 500]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

it('shows a public project with links to its expeditions and public page', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);
    $project = createPublicApiProject(['title' => 'Herbarium Labels']);

    $this->getJson(route('api.v1.projects.show', (string) $project->uuid))
        ->assertOk()
        ->assertJsonPath('data.title', 'Herbarium Labels')
        ->assertJsonPath('data.links.expeditions', route('api.v1.expeditions.index', ['project_uuid' => (string) $project->uuid]))
        ->assertJsonPath('data.links.page', route('front.projects.show', $project->slug))
        ->assertJsonMissingPath('data.contact_email');
});

it('returns 404 for a project that is not public', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);
    $project = Project::factory()->create();

    $this->getJson(route('api.v1.projects.show', (string) $project->uuid))->assertNotFound();
});
