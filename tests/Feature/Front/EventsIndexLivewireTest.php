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

use App\Livewire\Front\EventsIndex;
use App\Models\Event;
use App\Models\PanoptesProject;
use App\Models\Project;
use App\Services\Event\EventService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::forget('public_sort:events:version');
});

function makeEvent(array $overrides = []): Event
{
    $project = Project::factory()->create(['title' => $overrides['project_title'] ?? fake()->words(2, true)]);
    PanoptesProject::factory()->create(['project_id' => $project->id]);

    return Event::factory()->create(array_merge([
        'project_id' => $project->id,
        'title' => $overrides['title'] ?? fake()->words(2, true),
        'start_date' => $overrides['start_date'] ?? now()->addDays(1),
        'end_date' => $overrides['end_date'] ?? now()->addDays(2),
    ], $overrides));
}

it('initial render shows active by default', function () {
    $active = makeEvent(['title' => 'Active A', 'start_date' => now()->subDay(), 'end_date' => now()->addDay()]);
    $completed = makeEvent(['title' => 'Completed Z', 'start_date' => now()->subDays(5), 'end_date' => now()->subDays(1)]);

    Livewire::test(EventsIndex::class)
        ->assertSet('type', 'active')
        ->assertSee('Active A')
        ->assertDontSee('Completed Z');
});

it('explicit type param shows completed partition', function () {
    $active = makeEvent(['title' => 'Active A', 'start_date' => now()->subDay(), 'end_date' => now()->addDay()]);
    $completed = makeEvent(['title' => 'Completed Z', 'start_date' => now()->subDays(5), 'end_date' => now()->subDays(1)]);

    Livewire::test(EventsIndex::class, ['type' => 'completed'])
        ->assertSet('type', 'completed')
        ->assertSee('Completed Z')
        ->assertDontSee('Active A');
});

it('clicking Title sorts by title asc then toggles to desc', function () {
    makeEvent(['title' => 'Alpha']);
    makeEvent(['title' => 'Zebra']);

    Livewire::test(EventsIndex::class)
        ->call('sortBy', 'title')
        ->assertSet('sort', 'title')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Alpha', 'Zebra'])
        ->call('sortBy', 'title')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['Zebra', 'Alpha']);
});

it('clicking Project sorts by project asc; switching sort resets order to asc', function () {
    $pA = Project::factory()->create(['title' => 'A Project']);
    $pZ = Project::factory()->create(['title' => 'Z Project']);
    PanoptesProject::factory()->create(['project_id' => $pA->id]);
    PanoptesProject::factory()->create(['project_id' => $pZ->id]);

    Event::factory()->create(['project_id' => $pZ->id, 'title' => 'E1', 'start_date' => now()->addDays(1), 'end_date' => now()->addDays(2)]);
    Event::factory()->create(['project_id' => $pA->id, 'title' => 'E2', 'start_date' => now()->addDays(1), 'end_date' => now()->addDays(2)]);

    Livewire::test(EventsIndex::class)
        ->call('sortBy', 'project')
        ->assertSet('sort', 'project')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['E2', 'E1'])
        ->call('sortBy', 'project')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['E1', 'E2'])
        ->call('sortBy', 'title')
        ->assertSet('sort', 'title')
        ->assertSet('order', 'asc');
});

it('clicking Date sorts by date asc then toggles', function () {
    makeEvent(['title' => 'Old', 'start_date' => now()->addDays(1)]);
    makeEvent(['title' => 'New', 'start_date' => now()->addDays(2)]);

    Livewire::test(EventsIndex::class)
        ->call('sortBy', 'date')
        ->assertSet('sort', 'date')
        ->assertSet('order', 'desc')
        ->assertSeeInOrder(['New', 'Old'])
        ->call('sortBy', 'date')
        ->assertSet('order', 'asc')
        ->assertSeeInOrder(['Old', 'New']);
});

it('project-scoped rendering shows only events from that project', function () {
    $p1 = Project::factory()->create(['title' => 'A']);
    $p2 = Project::factory()->create(['title' => 'B']);
    PanoptesProject::factory()->create(['project_id' => $p1->id]);
    PanoptesProject::factory()->create(['project_id' => $p2->id]);

    Event::factory()->create(['project_id' => $p1->id, 'title' => 'P1 E']);
    Event::factory()->create(['project_id' => $p2->id, 'title' => 'P2 E']);

    Livewire::test(EventsIndex::class, ['projectId' => $p1->id])
        ->assertSee('P1 E')
        ->assertDontSee('P2 E');
});

it('calls EventService::getPublicIndexCachedData', function () {
    $p = Project::factory()->create(['title' => 'Proj']);
    PanoptesProject::factory()->create(['project_id' => $p->id]);
    $active = Event::factory()->create(['project_id' => $p->id, 'title' => 'Mock Active', 'start_date' => now()->subDay(), 'end_date' => now()->addDay()]);
    $completed = Event::factory()->create(['project_id' => $p->id, 'title' => 'Mock Completed', 'start_date' => now()->subDays(3), 'end_date' => now()->subDay()]);

    $service = app(EventService::class);
    $data = $service->getPublicIndex(['sort' => 'date', 'order' => 'asc']);

    $mock = $this->mock(EventService::class);
    $mock->shouldReceive('getPublicIndexCachedData')
        ->once()
        ->with(['sort' => 'date', 'order' => 'asc', 'projectId' => null])
        ->andReturn($data);

    Livewire::test(EventsIndex::class)
        ->assertSee('Mock Active');
});
