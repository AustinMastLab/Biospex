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

use App\Livewire\Front\ExpeditionsIndex;
use App\Models\Actor;
use App\Models\Expedition;
use App\Models\PanoptesProject;
use App\Models\Project;
use App\Services\Expedition\ExpeditionService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::forget('public_sort:expeditions:version');

    // Ensure Zooniverse actor exists for public query
    if (! Actor::where('id', config('zooniverse.actor_id'))->exists()) {
        Actor::factory()->create(['id' => config('zooniverse.actor_id')]);
    }

    \Illuminate\Support\Facades\Storage::fake('s3');
    config(['filesystems.disks.s3.bucket' => 'test-bucket']);
});

function makeExpedition(array $overrides = []): Expedition
{
    $project = Project::factory()->create(['title' => $overrides['project_title'] ?? fake()->words(2, true)]);

    $expedition = Expedition::factory()->create(array_merge([
        'project_id' => $project->id,
        'title' => $overrides['title'] ?? fake()->words(2, true),
        'completed' => $overrides['completed'] ?? 0,
        'created_at' => $overrides['created_at'] ?? now(),
    ], $overrides));

    \App\Models\ExpeditionStat::factory()->create([
        'expedition_id' => $expedition->id,
        'local_transcriptions_completed' => 10,
        'transcriber_count' => 5,
        'percent_completed' => 50,
    ]);

    PanoptesProject::factory()->create([
        'project_id' => $project->id,
        'expedition_id' => $expedition->id,
    ]);

    $expedition->actors()->attach(config('zooniverse.actor_id'));

    return $expedition;
}

it('renders active by default', function () {
    makeExpedition(['title' => 'Active Exp', 'completed' => 0]);
    makeExpedition(['title' => 'Completed Exp', 'completed' => 1]);

    Livewire::test(ExpeditionsIndex::class)
        ->assertSet('type', 'active')
        ->assertSee('Active Exp')
        ->assertDontSee('Completed Exp');
});

it('renders completed partition when type is set', function () {
    makeExpedition(['title' => 'Active Exp', 'completed' => 0]);
    makeExpedition(['title' => 'Completed Exp', 'completed' => 1]);

    Livewire::test(ExpeditionsIndex::class, ['type' => 'completed'])
        ->assertSet('type', 'completed')
        ->assertSee('Completed Exp')
        ->assertDontSee('Active Exp');
});

it('toggling sort/order updates ordering (title)', function () {
    makeExpedition(['title' => 'Alpha', 'completed' => 0]);
    makeExpedition(['title' => 'Zebra', 'completed' => 0]);

    Livewire::test(ExpeditionsIndex::class)
        ->call('sortBy', 'title')
        ->assertSet('sort', 'title')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Alpha', 'Zebra'])
        ->call('sortBy', 'title')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['Zebra', 'Alpha']);
});

it('toggling sort/order updates ordering (date)', function () {
    makeExpedition(['title' => 'Old', 'created_at' => now()->subDays(2), 'completed' => 0]);
    makeExpedition(['title' => 'New', 'created_at' => now(), 'completed' => 0]);

    Livewire::test(ExpeditionsIndex::class)
        ->call('sortBy', 'date')
        ->assertSet('sort', 'date')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['New', 'Old'])
        ->call('sortBy', 'date')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Old', 'New']);
});

it('switching sort resets order to asc', function () {
    Livewire::test(ExpeditionsIndex::class)
        ->set('sort', 'title')
        ->set('order', 'desc')
        ->call('sortBy', 'date')
        ->assertSet('sort', 'date')
        ->assertSet('order', 'asc');
});

it('project-scoped rendering works', function () {
    $p1 = Project::factory()->create();
    $p2 = Project::factory()->create();

    makeExpedition(['title' => 'Exp P1', 'project_id' => $p1->id]);
    makeExpedition(['title' => 'Exp P2', 'project_id' => $p2->id]);

    Livewire::test(ExpeditionsIndex::class, ['projectId' => $p1->id])
        ->assertSee('Exp P1')
        ->assertDontSee('Exp P2');
});

it('asserts component uses ExpeditionService::getPublicIndexCachedData', function () {
    $exp = makeExpedition(['title' => 'Mocked Exp']);

    $service = app(ExpeditionService::class);
    $data = $service->getPublicIndex(['sort' => 'date', 'order' => 'asc']);

    $mock = $this->mock(ExpeditionService::class);
    $mock->shouldReceive('getPublicIndexCachedData')
        ->once()
        ->with(['sort' => 'date', 'order' => 'asc', 'projectId' => null])
        ->andReturn($data);

    Livewire::test(ExpeditionsIndex::class)
        ->assertSee('Mocked Exp');
});
