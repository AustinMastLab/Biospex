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
use App\Models\EventTeam;
use App\Models\Expedition;
use App\Models\PanoptesProject;
use App\Models\Project;
use App\Models\ProjectDailyTranscription;
use App\Models\StateCounty;
use App\Services\Event\EventRateService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Config::set('filesystems.default', 's3');
    Config::set('filesystems.disks.s3.bucket', 'test-bucket');
    Storage::fake('s3');
});

/**
 * The text of the element that has focus.
 */
function focusedText(): string
{
    return 'document.activeElement.textContent.trim()';
}

it('draws the project page charts with data tables and no accessibility issues', function () {
    $project = Project::factory()->create();
    $expedition = Expedition::factory()->for($project)->create(['title' => 'Ferns of Florida']);
    ProjectDailyTranscription::insert([
        ['project_id' => $project->id, 'expedition_id' => $expedition->id, 'date' => '2023-03-01', 'count' => 4],
        ['project_id' => $project->id, 'expedition_id' => $expedition->id, 'date' => '2023-05-10', 'count' => 6],
    ]);
    StateCounty::factory()->create(['state_num' => '12', 'state_abbr_cap' => 'FL', 'county_name' => 'Leon', 'geo_id_2' => '12073']);

    $page = visit('/projects/'.$project->slug);
    $page->wait(2);

    $page->assertScript("document.querySelectorAll('#digitizations-chart path').length > 0", true)
        ->assertScript("document.querySelectorAll('#state-map path').length > 0", true)
        ->assertScript("document.querySelector('#digitizations-table tbody tr:last-child').textContent.includes('10')", true)
        ->assertScript("document.querySelector('#state-map-table tbody button').textContent", 'Florida')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

it('zooms the event rate chart from the keyboard, in tab order, with no accessibility issues', function () {
    $project = Project::factory()->create();
    PanoptesProject::factory()->create(['project_id' => $project->id]);
    $event = Event::factory()->for($project)->create(['start_date' => now()->subDays(2), 'end_date' => now()->addDay()]);
    EventTeam::factory()->for($event)->create(['title' => 'Fern Finders']);

    // Two days of 5-minute points; the real query uses MySQL date functions the test database lacks.
    $rates = Mockery::mock(EventRateService::class);
    $rates->shouldReceive('eventStepChart')->andReturn(collect(range(0, 575))->map(fn (int $step) => [
        'date' => now()->subDays(2)->startOfHour()->addMinutes($step * 5)->format('Y-m-d H:i:s'),
        'Fern Finders' => $step % 40,
    ])->all());
    app()->instance(EventRateService::class, $rates);

    $page = visit('/events/'.$event->uuid);
    $page->script("document.querySelector('[data-modal-target=\"#step-chart-modal\"]').click()");
    $page->wait(2);

    $status = "document.querySelector('#step-chart-modal [role=status]').textContent";
    $whole = $page->script($status);

    // From the modal's Close button, Tab reaches the zoom buttons in order.
    $page->script("document.querySelector('#step-chart-modal [data-modal-dismiss]').focus()");
    $page->keys(':focus', 'Tab');
    $page->assertScript(focusedText(), 'Zoom in');
    $page->keys(':focus', 'Enter');
    $page->assertScript("{$status} !== ".json_encode($whole), true);

    $page->keys(':focus', 'Tab');
    $page->assertScript(focusedText(), 'Zoom out');
    $page->keys(':focus', 'Enter');

    // Back to the whole range: Zoom out can't act any more but keeps focus.
    $page->assertScript($status, $whole)
        ->assertScript(focusedText(), 'Zoom out')
        ->assertScript("document.activeElement.getAttribute('aria-disabled')", 'true')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});
