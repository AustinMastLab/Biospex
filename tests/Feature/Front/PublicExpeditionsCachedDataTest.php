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
use App\Services\Expedition\ExpeditionService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('public_sort:expeditions:version');
});

function seedExpeditionsFixtures(): array
{
    // Ensure Zooniverse actor exists with the configured ID
    $actorId = (int) config('zooniverse.actor_id', 1);
    Actor::factory()->create(['id' => $actorId]);

    // Create two projects
    $p1 = Project::factory()->create(['title' => 'A Project']);
    $p2 = Project::factory()->create(['title' => 'Z Project']);

    // Create six expeditions across projects with deterministic titles/dates
    $e1 = Expedition::factory()->for($p1)->create(['title' => 'Alpha', 'created_at' => now()->subDays(6), 'completed' => 0]);
    $e2 = Expedition::factory()->for($p1)->create(['title' => 'Bravo', 'created_at' => now()->subDays(5), 'completed' => 0]);
    $e3 = Expedition::factory()->for($p2)->create(['title' => 'Charlie', 'created_at' => now()->subDays(4), 'completed' => 0]);
    $e4 = Expedition::factory()->for($p2)->create(['title' => 'Delta', 'created_at' => now()->subDays(3), 'completed' => 0]);
    $e5 = Expedition::factory()->for($p1)->create(['title' => 'Echo', 'created_at' => now()->subDays(2), 'completed' => 0]);
    $e6 = Expedition::factory()->for($p2)->create(['title' => 'Foxtrot', 'created_at' => now()->subDay(), 'completed' => 0]);

    // Prerequisites for public query: panoptesProject exists and Zooniverse actor on pivot
    foreach ([$e1, $e2, $e3, $e4, $e5, $e6] as $e) {
        PanoptesProject::factory()->create(['expedition_id' => $e->id, 'project_id' => $e->project_id]);
        // Attach the configured Zooniverse actor
        $e->actors()->attach($actorId, ['state' => ActorExpeditionState::Processing->value, 'total' => 0, 'error' => 0, 'order' => 1, 'expert' => 0]);
    }

    return [
        'projects' => [$p1, $p2],
        'expeditions' => [$e1, $e2, $e3, $e4, $e5, $e6],
    ];
}

it('caches each public expedition page', function () {
    ['expeditions' => [$expedition]] = seedExpeditionsFixtures();

    $service = app(ExpeditionService::class);
    $params = ['type' => 'active', 'sort' => 'title', 'order' => 'asc'];

    $firstPage = $service->getPublicIndexPage($params);
    $firstTitles = $firstPage->getCollection()->pluck('title')->all();

    $expedition->deleteQuietly();

    $secondPage = $service->getPublicIndexPage($params);

    expect($secondPage->getCollection()->pluck('title')->all())->toEqual($firstTitles);
});

it('refreshes cached expedition pages when an expedition is created (version bump)', function () {
    seedExpeditionsFixtures();
    $service = app(ExpeditionService::class);
    $params = ['type' => 'active', 'sort' => 'title', 'order' => 'asc'];

    $before = $service->getPublicIndexPage($params)->getCollection()->pluck('title');

    $project = Project::factory()->create(['title' => 'New Project']);
    $actorId = (int) config('zooniverse.actor_id', 1);
    $expedition = Expedition::factory()->for($project)->create(['title' => 'ZZZ New', 'created_at' => now(), 'completed' => 0]);
    PanoptesProject::factory()->create(['expedition_id' => $expedition->id, 'project_id' => $project->id]);
    $expedition->actors()->attach($actorId, ['state' => ActorExpeditionState::Processing->value, 'total' => 0, 'error' => 0, 'order' => 1, 'expert' => 0]);

    $after = $service->getPublicIndexPage($params)->getCollection()->pluck('title');

    expect($before)->not->toContain('ZZZ New')
        ->and($after)->toContain('ZZZ New');
});
