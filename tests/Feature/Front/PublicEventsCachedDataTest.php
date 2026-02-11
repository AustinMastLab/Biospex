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

use App\Models\Event;
use App\Models\Project;
use App\Services\Event\EventService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('returns events sorted by title asc/desc', function () {
    Cache::forget('public_sort:events:version');

    $p = Project::factory()->create(['title' => 'Project']);
    $e1 = Event::factory()->for($p)->create(['title' => 'Alpha', 'start_date' => now()->addDay()]);
    $e2 = Event::factory()->for($p)->create(['title' => 'Bravo', 'start_date' => now()->addDays(2)]);
    $e3 = Event::factory()->for($p)->create(['title' => 'Charlie', 'start_date' => now()->addDays(3)]);

    $service = app(EventService::class);

    $asc = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);
    // Returns active + completed partitions. Both are collections.
    // partition() returns [0 => matches, 1 => non-matches]
    expect($asc[0]->pluck('title')->all())->toBe(['Alpha', 'Bravo', 'Charlie']);

    $desc = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'desc']);
    expect($desc[0]->pluck('title')->all())->toBe(['Charlie', 'Bravo', 'Alpha']);
});

it('returns events sorted by project asc/desc', function () {
    Cache::forget('public_sort:events:version');

    $pA = Project::factory()->create(['title' => 'Alpha Project']);
    $pB = Project::factory()->create(['title' => 'Bravo Project']);

    $e1 = Event::factory()->for($pB)->create(['title' => 'Event 1', 'start_date' => now()->addDay()]);
    $e2 = Event::factory()->for($pA)->create(['title' => 'Event 2', 'start_date' => now()->addDay()]);

    $service = app(EventService::class);

    $asc = $service->getPublicIndexCachedData(['sort' => 'project', 'order' => 'asc']);
    expect($asc[0]->pluck('title')->all())->toBe(['Event 2', 'Event 1']);

    $desc = $service->getPublicIndexCachedData(['sort' => 'project', 'order' => 'desc']);
    expect($desc[0]->pluck('title')->all())->toBe(['Event 1', 'Event 2']);
});

it('returns events sorted by date asc/desc', function () {
    Cache::forget('public_sort:events:version');

    $p = Project::factory()->create();
    $old = Event::factory()->for($p)->create(['start_date' => now()->addDay(), 'title' => 'Old']);
    $mid = Event::factory()->for($p)->create(['start_date' => now()->addDays(2), 'title' => 'Mid']);
    $new = Event::factory()->for($p)->create(['start_date' => now()->addDays(3), 'title' => 'New']);

    $service = app(EventService::class);

    $asc = $service->getPublicIndexCachedData(['sort' => 'date', 'order' => 'asc']);
    expect($asc[0]->pluck('title')->all())->toBe(['Old', 'Mid', 'New']);

    $desc = $service->getPublicIndexCachedData(['sort' => 'date', 'order' => 'desc']);
    expect($desc[0]->pluck('title')->all())->toBe(['New', 'Mid', 'Old']);
});

it('returns project-scoped events', function () {
    Cache::forget('public_sort:events:version');

    $p1 = Project::factory()->create(['title' => 'P1']);
    $p2 = Project::factory()->create(['title' => 'P2']);

    $e1 = Event::factory()->for($p1)->create(['title' => 'E1', 'start_date' => now()->addDay()]);
    $e2 = Event::factory()->for($p2)->create(['title' => 'E2', 'start_date' => now()->addDay()]);

    $service = app(EventService::class);

    // Using 'id' should be normalized to 'projectId'
    $scoped = $service->getPublicIndexCachedData(['id' => $p1->id, 'sort' => 'title', 'order' => 'asc']);
    expect($scoped[0]->count())->toBe(1);
    expect($scoped[0]->first()->title)->toBe('E1');

    // Explicit 'projectId'
    $scoped2 = $service->getPublicIndexCachedData(['projectId' => $p2->id, 'sort' => 'title', 'order' => 'asc']);
    expect($scoped2[0]->count())->toBe(1);
    expect($scoped2[0]->first()->title)->toBe('E2');
});

it('uses cache on second call', function () {
    Cache::forget('public_sort:events:version');

    $p = Project::factory()->create();
    Event::factory()->for($p)->create(['title' => 'One', 'start_date' => now()->addDay()]);

    $service = app(EventService::class);

    DB::enableQueryLog();
    $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']); // primes cache
    $firstCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']); // should hit cache
    $secondCount = count(DB::getQueryLog());

    expect($firstCount)->toBeGreaterThan(0);
    expect($secondCount)->toBe(0);
});

it('invalidates cache when event is created or updated (version bump)', function () {
    Cache::forget('public_sort:events:version');

    $p = Project::factory()->create();
    $e1 = Event::factory()->for($p)->create(['title' => 'Alpha', 'start_date' => now()->addDay()]);

    $service = app(EventService::class);

    // Prime cache with Alpha
    $list1 = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);
    expect($list1[0]->pluck('title')->all())->toBe(['Alpha']);

    // Create Bravo -> observer should bump version
    $e2 = Event::factory()->for($p)->create(['title' => 'Bravo', 'start_date' => now()->addDay()]);

    // After version bump, cached-data should include Bravo
    $list2 = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);
    expect($list2[0]->pluck('title')->all())->toBe(['Alpha', 'Bravo']);

    // Update Bravo to Zebra -> version bump and order changes
    $e2->update(['title' => 'Zebra']);

    $list3 = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);
    expect($list3[0]->pluck('title')->all())->toBe(['Alpha', 'Zebra']);
});
