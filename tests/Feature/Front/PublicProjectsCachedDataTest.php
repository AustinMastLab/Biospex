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
use App\Services\Project\ProjectService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('refreshes cached project pages when a project is created or updated (version bump)', function () {
    Cache::forget('public_sort:projects:version');

    $p1 = Project::factory()->create(['title' => 'Alpha']);
    PanoptesProject::factory()->create(['project_id' => $p1->id]);

    $service = app(ProjectService::class);
    $params = ['sort' => 'title', 'order' => 'asc'];

    expect($service->getPublicIndexPage($params, 1)->pluck('title')->all())->toBe(['Alpha']);

    $p2 = Project::factory()->create(['title' => 'Bravo']);
    PanoptesProject::factory()->create(['project_id' => $p2->id]);

    expect($service->getPublicIndexPage($params, 1)->pluck('title')->all())->toBe(['Alpha', 'Bravo']);

    $p2->update(['title' => 'Zebra']);

    expect($service->getPublicIndexPage($params, 1)->pluck('title')->all())->toBe(['Alpha', 'Zebra']);
});

it('caches public project pages separately', function () {
    Cache::forget('public_sort:projects:version');

    $projects = Project::factory()->count(10)->sequence(
        fn ($sequence) => ['title' => sprintf('Project %02d', $sequence->index + 1)],
    )->create();
    $projects->each(fn (Project $project) => PanoptesProject::factory()->create(['project_id' => $project->id]));

    $service = app(ProjectService::class);

    $firstPage = $service->getPublicIndexPage(['sort' => 'title', 'order' => 'asc'], 1);
    $secondPage = $service->getPublicIndexPage(['sort' => 'title', 'order' => 'asc'], 2);

    expect($firstPage)
        ->toBeInstanceOf(Paginator::class)
        ->and($firstPage->pluck('title')->all())->toHaveCount(9)
        ->and($firstPage->hasMorePages())->toBeTrue()
        ->and($secondPage->pluck('title')->all())->toBe(['Project 10'])
        ->and($secondPage->hasMorePages())->toBeFalse();
});
