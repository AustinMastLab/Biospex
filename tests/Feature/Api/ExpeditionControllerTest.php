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

use App\Enums\ActorExpeditionState;
use App\Models\Actor;
use App\Models\Expedition;
use App\Models\PanoptesProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('s3');
});

/**
 * Expedition that the public site lists: it has a Panoptes project and the Zooniverse actor.
 */
function createPublicApiExpedition(
    ?Project $project = null,
    array $attributes = [],
    ActorExpeditionState $state = ActorExpeditionState::Processing,
): Expedition {
    $actorId = (int) config('zooniverse.actor_id');
    Actor::query()->find($actorId) ?? Actor::factory()->create(['id' => $actorId]);

    $project ??= Project::factory()->create();
    $expedition = Expedition::factory()->for($project)->create($attributes);
    PanoptesProject::factory()->for($project)->create(['expedition_id' => $expedition->id]);
    $expedition->actors()->attach($actorId, ['state' => $state->value, 'total' => 0, 'error' => 0, 'order' => 1, 'expert' => 0]);

    return $expedition;
}

it('returns 403 when the token lacks the public:read ability', function () {
    Sanctum::actingAs(User::factory()->create(), ['wedigbio-dashboard:read']);

    $this->getJson(route('api.v1.expeditions.index'))->assertForbidden();
});

it('lists only expeditions running on Zooniverse', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);
    $public = createPublicApiExpedition();
    Expedition::factory()->create();

    $this->getJson(route('api.v1.expeditions.index'))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.uuid', (string) $public->uuid);
});

it('lists only the expeditions of the requested project', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);
    $project = Project::factory()->create();
    $expedition = createPublicApiExpedition($project);
    createPublicApiExpedition();

    $this->getJson(route('api.v1.expeditions.index', ['project_uuid' => (string) $project->uuid]))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.uuid', (string) $expedition->uuid)
        ->assertJsonPath('data.0.project.uuid', (string) $project->uuid);
});

it('lists only expeditions with the requested status', function (string $status, bool $completed) {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);
    $expedition = createPublicApiExpedition(attributes: ['completed' => $completed]);
    createPublicApiExpedition(attributes: ['completed' => ! $completed]);

    $this->getJson(route('api.v1.expeditions.index', ['status' => $status]))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.uuid', (string) $expedition->uuid);
})->with([
    'active' => ['active', false],
    'completed' => ['completed', true],
]);

it('shows a live expedition with its Zooniverse participate link', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);
    $expedition = createPublicApiExpedition();
    $expedition->panoptesProject->update(['slug' => 'biospex/herbarium', 'panoptes_workflow_id' => 4321]);

    $this->getJson(route('api.v1.expeditions.show', (string) $expedition->uuid))
        ->assertOk()
        ->assertJsonPath('data.uuid', (string) $expedition->uuid)
        ->assertJsonPath('data.links.project', route('api.v1.projects.show', (string) $expedition->project->uuid))
        ->assertJsonPath('data.links.participate', 'https://www.zooniverse.org/projects/biospex/herbarium/classify?workflow=4321');
});

it('omits the participate link once the expedition has left Zooniverse', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);
    $expedition = createPublicApiExpedition(state: ActorExpeditionState::Complete);

    $this->getJson(route('api.v1.expeditions.show', (string) $expedition->uuid))
        ->assertOk()
        ->assertJsonPath('data.links.participate', null);
});

it('returns 404 for an expedition that is not public', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);
    $expedition = Expedition::factory()->create();

    $this->getJson(route('api.v1.expeditions.show', (string) $expedition->uuid))->assertNotFound();
});
