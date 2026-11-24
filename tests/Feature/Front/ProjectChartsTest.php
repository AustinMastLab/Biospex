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

use App\Jobs\RefreshProjectChartJob;
use App\Models\Expedition;
use App\Models\Project;
use App\Models\ProjectDailyTranscription;
use App\Services\Chart\ProjectTranscriptionChart;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Spatie\ResponseCache\Facades\ResponseCache;

beforeEach(function () {
    Config::set('filesystems.default', 's3');
    Config::set('filesystems.disks.s3.bucket', 'test-bucket');
    Storage::fake('s3');
});

it('shows the Digitizations chart with the project\'s chart years, and the heat map', function () {
    $project = Project::factory()->create();
    $expedition = Expedition::factory()->for($project)->create();
    ProjectDailyTranscription::insert([
        ['project_id' => $project->id, 'expedition_id' => $expedition->id, 'date' => '2023-06-01', 'count' => 4],
        ['project_id' => $project->id, 'expedition_id' => $expedition->id, 'date' => '2025-02-10', 'count' => 2],
    ]);

    $this->get(route('front.projects.show', $project->slug))
        ->assertOk()
        ->assertSeeInOrder(['data-digitizations-year="2025"', 'data-digitizations-year="2023"'], false)
        ->assertSee('id="state-map"', false);
});

it('shows neither chart for a project without chart data', function () {
    $project = Project::factory()->create();

    $this->get(route('front.projects.show', $project->slug))
        ->assertOk()
        ->assertDontSee('id="digitizations-chart"', false)
        ->assertDontSee('id="state-map"', false);
});

it('refreshes the chart data and clears the cached pages that show it', function () {
    config(['responsecache.enabled' => true]);
    $project = Project::factory()->create();
    $chart = Mockery::mock(ProjectTranscriptionChart::class);
    $chart->shouldReceive('refresh')->once()->withArgs(fn (Project $refreshed) => $refreshed->is($project))->andReturn(0);
    ResponseCache::spy();

    (new RefreshProjectChartJob($project))->handle($chart);

    ResponseCache::shouldHaveReceived('clear')->once();
});
