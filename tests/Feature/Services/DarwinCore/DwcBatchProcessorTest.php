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

use App\Models\Project;
use App\Models\Subject;
use App\Services\DarwinCore\DwcBatchProcessor;

beforeEach(function () {
    Subject::truncate();
    $this->project = Project::factory()->create();
});

afterEach(function () {
    Subject::truncate();
});

/**
 * Import the small fixture archive: 5 media rows, of which 2 are rejected and 1 is already stored.
 *
 * @return array{processor: DwcBatchProcessor, result: array<string, mixed>}
 */
function importFixtureArchive(Project $project): array
{
    $processor = app(DwcBatchProcessor::class);
    $result = $processor->processArchive($project->id, base_path('tests/Fixtures/dwc/basic'));

    return ['processor' => $processor, 'result' => $result];
}

it('creates a subject for each valid media row, with its occurrence data', function () {
    ['result' => $result] = importFixtureArchive($this->project);

    expect($result)->toMatchArray(['success' => true, 'subjects_created' => 3, 'duplicates_count' => 0, 'rejected_count' => 2]);

    $subject = Subject::where('project_id', $this->project->id)->where('imageId', '1b4e28ba-2fa1-11d2-883f-0016d3cca427')->sole();

    expect($subject->accessURI)->toBe('https://images.example.org/occ-1.jpg')
        ->and(data_get($subject->occurrence, 'scientificName'))->toBe('Quercus alba')
        ->and(data_get($subject->occurrence, 'catalogNumber'))->toBe('FLAS-001');
});

it('rejects media rows without an identifier or an image URL', function () {
    ['processor' => $processor] = importFixtureArchive($this->project);

    expect(collect($processor->getRejectedMedia())->pluck('Reason')->all())->toEqualCanonicalizing([
        'All identifier columns empty, identifier is URL, or invalid URN format.',
        'Missing accessURI.',
    ]);
});

it('skips images the project already has', function () {
    Subject::create(['project_id' => $this->project->id, 'imageId' => '3d6a40dc-4bc3-13f4-aa5b-0038f5eec649']);

    ['processor' => $processor, 'result' => $result] = importFixtureArchive($this->project);

    expect($result)->toMatchArray(['subjects_created' => 2, 'duplicates_count' => 1, 'rejected_count' => 2])
        ->and($processor->getDuplicates())->toHaveCount(1)
        ->and($processor->getDuplicates()[0])->toMatchArray(['Reason' => 'Duplicate imageId in database.', 'imageId' => '3d6a40dc-4bc3-13f4-aa5b-0038f5eec649'])
        ->and(collect($processor->getRejectedMedia())->pluck('Reason'))->not->toContain('Duplicate imageId in database.')
        ->and(Subject::where('project_id', $this->project->id)->where('imageId', '3d6a40dc-4bc3-13f4-aa5b-0038f5eec649')->count())->toBe(1);
});

it('imports a Symbiota-style archive whose multimedia is one of several extensions', function () {
    // Structure of a real Symbiota export (Identification, Multimedia, MeasurementOrFact); all rows are invented.
    $processor = app(DwcBatchProcessor::class);
    $result = $processor->processArchive($this->project->id, base_path('tests/Fixtures/dwc/symbiota'));

    expect($result)->toMatchArray(['success' => true, 'subjects_created' => 3, 'rejected_count' => 1])
        ->and(collect($processor->getRejectedMedia())->pluck('Reason')->all())
        ->toBe(['All identifier columns empty, identifier is URL, or invalid URN format.']);

    expect(Subject::where('project_id', $this->project->id)->pluck('accessURI')->sort()->values()->all())->toBe([
        'https://images.example.org/test/1001-b.jpg',
        'https://images.example.org/test/1001.jpg',
        'https://images.example.org/test/1002.jpg',
    ]);

    $subjectFor = fn (string $accessUri) => Subject::where('project_id', $this->project->id)->where('accessURI', $accessUri)->sole();

    expect(data_get($subjectFor('https://images.example.org/test/1001.jpg')->occurrence, 'scientificName'))->toBe('Quercus alba')
        ->and(data_get($subjectFor('https://images.example.org/test/1002.jpg')->occurrence, 'catalogNumber'))->toBe('TEST-0002');
});

it('treats an image in another project as new', function () {
    Subject::create(['project_id' => Project::factory()->create()->id, 'imageId' => '3d6a40dc-4bc3-13f4-aa5b-0038f5eec649']);

    ['result' => $result] = importFixtureArchive($this->project);

    expect($result)->toMatchArray(['subjects_created' => 3, 'duplicates_count' => 0]);
});
