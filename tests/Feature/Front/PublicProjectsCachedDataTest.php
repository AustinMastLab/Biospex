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

use App\Models\Group;
use App\Models\PanoptesProject;
use App\Models\Project;
use App\Services\Project\ProjectService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('returns projects sorted by title asc/desc', function () {
    Cache::forget('public_sort:projects:version');

    $a = Project::factory()->create(['title' => 'Alpha']);
    $b = Project::factory()->create(['title' => 'Bravo']);
    $c = Project::factory()->create(['title' => 'Charlie']);

    // Ensure they are eligible for public listing
    PanoptesProject::factory()->create(['project_id' => $a->id]);
    PanoptesProject::factory()->create(['project_id' => $b->id]);
    PanoptesProject::factory()->create(['project_id' => $c->id]);

    $service = app(ProjectService::class);

    $asc = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);
    expect($asc->pluck('title')->all())->toBe(['Alpha', 'Bravo', 'Charlie']);

    $desc = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'desc']);
    expect($desc->pluck('title')->all())->toBe(['Charlie', 'Bravo', 'Alpha']);
});

it('returns projects sorted by group asc/desc', function () {
    Cache::forget('public_sort:projects:version');

    $g1 = Group::factory()->create(['title' => 'A Group']);
    $g2 = Group::factory()->create(['title' => 'B Group']);

    $p1 = Project::factory()->for($g2)->create(['title' => 'X']);
    $p2 = Project::factory()->for($g1)->create(['title' => 'Y']);

    PanoptesProject::factory()->create(['project_id' => $p1->id]);
    PanoptesProject::factory()->create(['project_id' => $p2->id]);

    $service = app(ProjectService::class);

    $asc = $service->getPublicIndexCachedData(['sort' => 'group', 'order' => 'asc']);
    expect($asc->pluck('id')->all())->toBe([$p2->id, $p1->id]);

    $desc = $service->getPublicIndexCachedData(['sort' => 'group', 'order' => 'desc']);
    expect($desc->pluck('id')->all())->toBe([$p1->id, $p2->id]);
});

it('returns projects sorted by date asc/desc', function () {
    Cache::forget('public_sort:projects:version');

    $old = Project::factory()->create(['created_at' => now()->subDays(2), 'title' => 'Old']);
    $mid = Project::factory()->create(['created_at' => now()->subDay(), 'title' => 'Mid']);
    $new = Project::factory()->create(['created_at' => now(), 'title' => 'New']);

    PanoptesProject::factory()->create(['project_id' => $old->id]);
    PanoptesProject::factory()->create(['project_id' => $mid->id]);
    PanoptesProject::factory()->create(['project_id' => $new->id]);

    $service = app(ProjectService::class);

    $asc = $service->getPublicIndexCachedData(['sort' => 'date', 'order' => 'asc']);
    expect($asc->pluck('title')->all())->toBe(['Old', 'Mid', 'New']);

    $desc = $service->getPublicIndexCachedData(['sort' => 'date', 'order' => 'desc']);
    expect($desc->pluck('title')->all())->toBe(['New', 'Mid', 'Old']);
});

it('uses cache on second call', function () {
    Cache::forget('public_sort:projects:version');

    $p = Project::factory()->create(['title' => 'One']);
    PanoptesProject::factory()->create(['project_id' => $p->id]);

    $service = app(ProjectService::class);

    DB::enableQueryLog();
    $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']); // primes cache
    $firstCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']); // should hit cache
    $secondCount = count(DB::getQueryLog());

    expect($firstCount)->toBeGreaterThan(0);
    expect($secondCount)->toBe(0);
});

it('invalidates cache when project is created or updated (version bump)', function () {
    Cache::forget('public_sort:projects:version');

    $p1 = Project::factory()->create(['title' => 'Alpha']);
    PanoptesProject::factory()->create(['project_id' => $p1->id]);

    $service = app(ProjectService::class);

    // Prime cache with only Alpha
    $list1 = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);
    expect($list1->pluck('title')->all())->toBe(['Alpha']);

    // Create Bravo -> observer should bump version
    $p2 = Project::factory()->create(['title' => 'Bravo']);
    PanoptesProject::factory()->create(['project_id' => $p2->id]);

    // After version bump, cached-data should include Bravo as well
    $list2 = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);
    expect($list2->pluck('title')->all())->toBe(['Alpha', 'Bravo']);

    // Update Bravo title to Zebra -> version bump again and order changes
    $p2->update(['title' => 'Zebra']);

    $list3 = $service->getPublicIndexCachedData(['sort' => 'title', 'order' => 'asc']);
    expect($list3->pluck('title')->all())->toBe(['Alpha', 'Zebra']);
});
