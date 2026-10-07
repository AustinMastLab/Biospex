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

it('caches each public event page within the current minute', function () {
    $event = Event::factory()->for(Project::factory())->create([
        'title' => 'Cached Event',
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(2),
    ]);

    $service = app(EventService::class);
    $params = ['type' => 'active', 'sort' => 'title', 'order' => 'asc'];

    $firstPage = $service->getPublicIndexPage($params);
    $event->deleteQuietly();

    expect($service->getPublicIndexPage($params)->getCollection()->pluck('title')->all())
        ->toEqual($firstPage->getCollection()->pluck('title')->all());
});

it('refreshes cached event pages when an event is created or updated (version bump)', function () {
    Cache::forget('public_sort:events:version');

    $p = Project::factory()->create();
    Event::factory()->for($p)->create(['title' => 'Alpha', 'start_date' => now()->addDay(), 'end_date' => now()->addDays(2)]);

    $service = app(EventService::class);
    $params = ['type' => 'active', 'sort' => 'title', 'order' => 'asc'];
    $titles = fn () => $service->getPublicIndexPage($params)->getCollection()->pluck('title')->all();

    expect($titles())->toBe(['Alpha']);

    $e2 = Event::factory()->for($p)->create(['title' => 'Bravo', 'start_date' => now()->addDay(), 'end_date' => now()->addDays(2)]);

    expect($titles())->toBe(['Alpha', 'Bravo']);

    $e2->update(['title' => 'Zebra']);

    expect($titles())->toBe(['Alpha', 'Zebra']);
});
