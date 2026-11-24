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

use App\Models\Expedition;
use App\Models\PanoptesProject;
use App\Models\PanoptesTranscription;
use App\Models\Project;
use App\Models\ProjectDailyTranscription;
use App\Services\Chart\ProjectTranscriptionChart;
use Illuminate\Support\Carbon;

beforeEach(function () {
    PanoptesTranscription::truncate();
});

afterEach(function () {
    PanoptesTranscription::truncate();
});

/**
 * An expedition on Zooniverse with the given workflow.
 */
function chartExpedition(Project $project, int $workflowId, string $title): Expedition
{
    $expedition = Expedition::factory()->for($project)->create(['title' => $title]);
    PanoptesProject::factory()->create(['project_id' => $project->id, 'expedition_id' => $expedition->id, 'panoptes_workflow_id' => $workflowId]);

    return $expedition;
}

/**
 * Transcriptions for a workflow finished at the given times (UTC).
 *
 * @param  array<int, string>  $finishedAt
 */
function chartTranscriptions(int $workflowId, array $finishedAt): void
{
    foreach ($finishedAt as $time) {
        PanoptesTranscription::create(['workflow_id' => $workflowId, 'classification_finished_at' => Carbon::parse($time, 'UTC')]);
    }
}

it('counts transcriptions per expedition per day', function () {
    $project = Project::factory()->create();
    $ferns = chartExpedition($project, 9001, 'Ferns');
    $mosses = chartExpedition($project, 9002, 'Mosses');
    chartTranscriptions(9001, ['2024-03-01 08:00', '2024-03-01 20:00', '2024-03-03 09:00']);
    chartTranscriptions(9002, ['2024-03-02 10:00']);
    chartTranscriptions(9999, ['2024-03-01 10:00']);

    $days = app(ProjectTranscriptionChart::class)->refresh($project, full: true);

    expect($days)->toBe(3)
        ->and(ProjectDailyTranscription::orderBy('date')->get(['expedition_id', 'date', 'count'])->map(fn ($day) => [$day->expedition_id, $day->date->toDateString(), $day->count])->all())
        ->toBe([[$ferns->id, '2024-03-01', 2], [$mosses->id, '2024-03-02', 1], [$ferns->id, '2024-03-03', 1]]);
});

it('counts again only from the last stored day unless asked for every day', function () {
    $project = Project::factory()->create();
    chartExpedition($project, 9001, 'Ferns');
    chartTranscriptions(9001, ['2024-03-01 08:00', '2024-03-05 08:00']);
    $chart = app(ProjectTranscriptionChart::class);
    $chart->refresh($project, full: true);
    chartTranscriptions(9001, ['2024-03-05 18:00', '2024-03-06 08:00']);

    $written = $chart->refresh($project);

    expect($written)->toBe(2)
        ->and(ProjectDailyTranscription::orderBy('date')->pluck('count', 'date')->mapWithKeys(fn ($count, $date) => [Carbon::parse($date)->toDateString() => $count])->all())
        ->toBe(['2024-03-01' => 1, '2024-03-05' => 2, '2024-03-06' => 1]);
});

it('serves a year as every day with each expedition\'s running total', function () {
    $project = Project::factory()->create();
    $ferns = chartExpedition($project, 9001, 'Ferns');
    chartExpedition($project, 9002, 'Mosses');
    chartTranscriptions(9001, ['2023-01-01 08:00', '2023-01-01 09:00', '2023-01-03 08:00', '2024-02-01 08:00']);
    $chart = app(ProjectTranscriptionChart::class);
    $chart->refresh($project, full: true);

    $year = $chart->year($project, 2023);

    expect($chart->years($project))->toBe([2024, 2023])
        ->and($year['dates'])->toHaveCount(365)
        ->and($year['dates'][0])->toBe('2023-01-01')
        ->and($year['series'])->toHaveCount(1)
        ->and($year['series'][0])->toMatchArray(['expeditionId' => $ferns->id, 'name' => 'Ferns', 'total' => 3])
        ->and(array_slice($year['series'][0]['values'], 0, 4))->toBe([2, 2, 3, 3])
        ->and(end($year['series'][0]['values']))->toBe(3);
});

it('serves a year from the public endpoint', function () {
    $project = Project::factory()->create();
    chartExpedition($project, 9001, 'Ferns');
    chartTranscriptions(9001, ['2023-06-01 08:00']);
    app(ProjectTranscriptionChart::class)->refresh($project, full: true);

    $this->getJson(route('front.projects.digitizations', [$project, 2023]))
        ->assertOk()
        ->assertJsonPath('year', 2023)
        ->assertJsonPath('series.0.name', 'Ferns')
        ->assertJsonPath('series.0.total', 1);
});
