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
        $e->actors()->attach($actorId, ['state' => 'ready', 'total' => 0, 'error' => 0, 'order' => 1, 'expert' => 0]);
    }

    return [
        'projects' => [$p1, $p2],
        'expeditions' => [$e1, $e2, $e3, $e4, $e5, $e6],
    ];
}

it('returns expeditions sorted by title asc/desc (unscoped)', function () {
    seedExpeditionsFixtures();

    $service = app(ExpeditionService::class);

    [$activeAsc, $completedAsc] = $service->getPublicIndexCachedData([
        'sort' => 'title',
        'order' => 'asc',
    ]);

    $mergedAsc = $activeAsc->merge($completedAsc)->pluck('title')->values()->all();
    $sortedAsc = $mergedAsc;
    sort($sortedAsc, SORT_STRING);
    expect($mergedAsc)->toEqual($sortedAsc);

    [$activeDesc, $completedDesc] = $service->getPublicIndexCachedData([
        'sort' => 'title',
        'order' => 'desc',
    ]);
    $mergedDesc = $activeDesc->merge($completedDesc)->pluck('title')->values()->all();
    $sortedDesc = $sortedAsc;
    $sortedDesc = array_reverse($sortedDesc);
    expect($mergedDesc)->toEqual($sortedDesc);
});

it('returns expeditions sorted by date asc/desc (unscoped)', function () {
    seedExpeditionsFixtures();

    $service = app(ExpeditionService::class);

    [$activeAsc, $completedAsc] = $service->getPublicIndexCachedData([
        'sort' => 'date',
        'order' => 'asc',
    ]);
    $datesAsc = $activeAsc->merge($completedAsc)->pluck('created_at')->values()->all();
    $sortedAsc = $datesAsc;
    sort($sortedAsc);
    expect($datesAsc)->toEqual($sortedAsc);

    [$activeDesc, $completedDesc] = $service->getPublicIndexCachedData([
        'sort' => 'date',
        'order' => 'desc',
    ]);
    $datesDesc = $activeDesc->merge($completedDesc)->pluck('created_at')->values()->all();
    $sortedDesc = $sortedAsc;
    $sortedDesc = array_reverse($sortedDesc);
    expect($datesDesc)->toEqual($sortedDesc);
});

it('returns project-scoped expeditions for a given projectId', function () {
    ['projects' => [$p1, $p2]] = seedExpeditionsFixtures();
    $service = app(ExpeditionService::class);

    [$activeP1, $completedP1] = $service->getPublicIndexCachedData([
        'projectId' => $p1->id,
        'sort' => 'title',
        'order' => 'asc',
    ]);

    $allP1 = $activeP1->merge($completedP1);
    expect($allP1->every(fn ($e) => (int) $e->project_id === (int) $p1->id))->toBeTrue();

    [$activeP2, $completedP2] = $service->getPublicIndexCachedData([
        'id' => $p2->id, // legacy param normalized to projectId
        'sort' => 'date',
        'order' => 'asc',
    ]);
    $allP2 = $activeP2->merge($completedP2);
    expect($allP2->every(fn ($e) => (int) $e->project_id === (int) $p2->id))->toBeTrue();
});

it('uses cache on second call with same params', function () {
    seedExpeditionsFixtures();

    $service = app(ExpeditionService::class);

    // Prime cache
    $service->getPublicIndexCachedData(['sort' => 'date', 'order' => 'asc']);

    // Spy on underlying getPublicIndex to ensure it is NOT called again
    $mock = $this->partialMock(ExpeditionService::class);
    $mock->shouldReceive('getPublicIndex')->never();

    $mock->getPublicIndexCachedData(['sort' => 'date', 'order' => 'asc']);
    // If it tried to call getPublicIndex, the expectation would fail
    expect(true)->toBeTrue();
});

it('invalidates cache when expedition is created or updated (version bump)', function () {
    seedExpeditionsFixtures();
    $service = app(ExpeditionService::class);

    [$activeBefore] = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);

    // Create a new expedition that meets public query requirements
    $project = Project::factory()->create(['title' => 'New Project']);
    $actorId = (int) config('zooniverse.actor_id', 1);
    $expedition = Expedition::factory()->for($project)->create(['title' => 'ZZZ New', 'created_at' => now(), 'completed' => 0]);
    PanoptesProject::factory()->create(['expedition_id' => $expedition->id, 'project_id' => $project->id]);
    $expedition->actors()->attach($actorId, ['state' => 'ready', 'total' => 0, 'error' => 0, 'order' => 1, 'expert' => 0]);

    // Version bump happens via observer on create; now fetch again
    [$activeAfter, $completedAfter] = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);

    $allAfter = $activeAfter->merge($completedAfter)->pluck('title');
    expect($allAfter)->toContain('ZZZ New');
});
