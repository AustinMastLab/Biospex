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
use App\Models\Event;
use App\Models\Expedition;
use App\Models\PanoptesProject;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

beforeEach(function () {
    Cache::forget('public_sort:events:version');
    Cache::forget('public_sort:expeditions:version');
    // Configure and fake S3 to satisfy presenters that check S3
    \Illuminate\Support\Facades\Config::set('filesystems.default', 's3');
    \Illuminate\Support\Facades\Config::set('filesystems.disks.s3.bucket', 'test-bucket');
    \Illuminate\Support\Facades\Storage::fake('s3');
});

function makeProjectWithPublicData(string $projectTitle, array $eventTitles = [], array $expeditionTitles = []): Project
{
    $project = Project::factory()->create(['title' => $projectTitle]);
    PanoptesProject::factory()->create(['project_id' => $project->id]);

    // Ensure Zooniverse actor exists to satisfy ExpeditionService public query
    $actorId = Config::get('zooniverse.actor_id', 1);
    if (! Actor::query()->where('id', $actorId)->exists()) {
        // Create an actor with the configured ID if possible
        $actor = Actor::factory()->create();
        // Overwrite config to this actor id for test determinism
        Config::set('zooniverse.actor_id', $actor->getKey());
        $actorId = $actor->getKey();
    }

    foreach ($eventTitles as $title) {
        Event::factory()->create([
            'project_id' => $project->id,
            'title' => $title,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
        ]);
    }

    foreach ($expeditionTitles as $title) {
        /** @var Expedition $expedition */
        $expedition = Expedition::factory()->create([
            'project_id' => $project->id,
            'title' => $title,
            'completed' => 0,
        ]);

        // Ensure a stat record exists for the expedition to satisfy view expectations
        \App\Models\ExpeditionStat::factory()->create([
            'expedition_id' => $expedition->id,
            'local_transcriptions_completed' => 0,
        ]);

        // Attach Zooniverse actor on pivot and create PanoptesProject to satisfy public filters
        $expedition->actors()->attach($actorId, ['order' => 1]);
        PanoptesProject::factory()->create(['project_id' => $project->id, 'expedition_id' => $expedition->id]);
    }

    return $project;
}

it('embeds Livewire components on project show page and scopes by projectId', function () {
    $p1 = makeProjectWithPublicData('Proj One', ['P1 E1', 'P1 E2'], ['P1 X1', 'P1 X2']);
    $p2 = makeProjectWithPublicData('Proj Two', ['P2 E1'], ['P2 X1']);

    // Visit project show page for p1
    $response = $this->get(route('front.projects.show', ['slug' => $p1->slug]));
    $response->assertOk();

    // Should see only p1 records initially
    $response->assertSee('P1 E1')
        ->assertSee('P1 E2')
        ->assertDontSee('P2 E1')
        ->assertSee('P1 X1')
        ->assertSee('P1 X2')
        ->assertDontSee('P2 X1');
});

it('multiple component instances do not conflict between events and expeditions', function () {
    $p = makeProjectWithPublicData('Proj', ['Alpha Event', 'Zebra Event'], ['Alpha Exp', 'Zebra Exp']);

    // Two independent component instances (events vs expeditions) with same projectId
    $events = Livewire::test(\App\Livewire\Front\EventsIndex::class, ['projectId' => $p->id, 'type' => 'active'])
        ->call('sortBy', 'title') // asc
        ->assertSeeInOrder(['Alpha Event', 'Zebra Event'])
        ->call('sortBy', 'title') // desc
        ->assertSeeInOrder(['Zebra Event', 'Alpha Event']);

    $expeditions = Livewire::test(\App\Livewire\Front\ExpeditionsIndex::class, ['projectId' => $p->id, 'type' => 'active'])
        ->assertSeeInOrder(['Alpha Exp', 'Zebra Exp']); // default date asc may not guarantee order; titles distinct still visible

    // Now, interact with expeditions and ensure events output remains unaffected
    $expeditions->call('sortBy', 'title') // asc
        ->assertSeeInOrder(['Alpha Exp', 'Zebra Exp'])
        ->call('sortBy', 'title') // desc
        ->assertSeeInOrder(['Zebra Exp', 'Alpha Exp']);

    // Re-assert events ordering still as last asserted (desc) to prove no cross-update
    $events->assertSeeInOrder(['Zebra Event', 'Alpha Event']);
});
