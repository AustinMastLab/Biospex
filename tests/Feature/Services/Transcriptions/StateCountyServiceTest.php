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
use App\Models\Project;
use App\Models\StateCounty;
use App\Models\TranscriptionLocation;
use App\Services\Transcriptions\StateCountyService;

function county(string $stateNum, string $stateAbbr): StateCounty
{
    return StateCounty::factory()->create(['state_num' => $stateNum, 'state_abbr_cap' => $stateAbbr]);
}

function locations(Project $project, StateCounty $county, int $count): void
{
    static $classificationId = 1000;
    $expedition = Expedition::factory()->for($project)->create();

    foreach (range(1, $count) as $ignored) {
        TranscriptionLocation::factory()->create([
            'classification_id' => $classificationId++,
            'project_id' => $project->id,
            'expedition_id' => $expedition->id,
            'state_county_id' => $county->id,
        ]);
    }
}

it('counts a project\'s transcriptions per state, listing every state with 0 where it has none', function () {
    $project = Project::factory()->create();
    $leon = county('12', 'FL');
    $escambia = county('12', 'FL');
    $travis = county('48', 'TX');
    county('6', 'CA');
    locations($project, $leon, 2);
    locations($project, $escambia, 1);
    locations($project, $travis, 1);
    locations(Project::factory()->create(), $travis, 5);

    $states = app(StateCountyService::class)->getStateTranscriptCount($project->id)->keyBy('id');

    expect($states->map(fn (array $state) => $state['value'])->sortKeys()->all())
        ->toBe(['US-CA' => 0, 'US-FL' => 3, 'US-TX' => 1])
        ->and($states['US-FL'])->toBe(['id' => 'US-FL', 'value' => 3, 'name' => 'FL', 'statenum' => '12']);
});

it('keeps the counts until they are refreshed', function () {
    $project = Project::factory()->create();
    $leon = county('12', 'FL');
    locations($project, $leon, 1);
    $service = app(StateCountyService::class);
    $service->getStateTranscriptCount($project->id);
    locations($project, $leon, 2);

    $cached = $service->getStateTranscriptCount($project->id)->firstWhere('id', 'US-FL')['value'];
    $service->refreshStateTranscriptCount($project->id);
    $refreshed = $service->getStateTranscriptCount($project->id)->firstWhere('id', 'US-FL')['value'];

    expect($cached)->toBe(1)
        ->and($refreshed)->toBe(3);
});
