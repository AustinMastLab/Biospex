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

use App\Facades\TranscriptionMapHelper;
use App\Models\PanoptesTranscription;
use App\Models\Subject;
use App\Services\Transcriptions\CreatePanoptesTranscriptionService;

beforeEach(function () {
    PanoptesTranscription::truncate();
    Subject::truncate();

    // Loaded from the database: a just-created Mongo model has no _id yet.
    Subject::factory()->create();
    $this->subject = Subject::sole();
    $this->service = app(CreatePanoptesTranscriptionService::class);
});

afterEach(function () {
    PanoptesTranscription::truncate();
    Subject::truncate();
});

/**
 * A reconciled transcript row as it comes from the expedition's CSV.
 *
 * @return array<string, mixed>
 */
function transcriptRow(Subject $subject, array $overrides = []): array
{
    return array_merge([
        'classification_id' => '778691035',
        'subject_subjectId' => (string) $subject->_id,
        'subject_expeditionId' => '12',
        'user_name' => 'volunteer',
        'Country' => 'United States',
        'County' => 'Alachua',
        'Collector Name' => 'A. Botanist',
    ], $overrides);
}

function importRow(CreatePanoptesTranscriptionService $service, array $row): void
{
    $service->processRow(array_keys($row), $row, 12);
}

/**
 * The errors the import collected, without writing a CSV report.
 *
 * @return array<int, array<string, mixed>>
 */
function importErrors(CreatePanoptesTranscriptionService $service): array
{
    return (fn () => $this->csvError)->call($service);
}

it('stores a CSV row as a Panoptes transcription with encoded field names', function () {
    importRow($this->service, transcriptRow($this->subject));

    $transcription = PanoptesTranscription::where('classification_id', 778691035)->sole();

    expect($transcription->subject_projectId)->toBe($this->subject->project_id)
        ->and($transcription->subject_subjectId)->toBe((string) $this->subject->_id)
        ->and($transcription->Country)->toBe('United States')
        ->and($transcription->{TranscriptionMapHelper::encodeTranscriptionField('Collector Name')})->toBe('A. Botanist')
        ->and($transcription->getAttributes())->not->toHaveKey('Collector Name')
        ->and(importErrors($this->service))->toBe([]);
});

it('skips a classification that is already stored', function () {
    importRow($this->service, transcriptRow($this->subject));
    importRow($this->service, transcriptRow($this->subject, ['County' => 'Leon']));

    expect(PanoptesTranscription::where('classification_id', 778691035)->count())->toBe(1);
});

it('records an error for a row whose subject does not exist', function () {
    importRow($this->service, transcriptRow($this->subject, ['subject_subjectId' => '0123456789abcdef01234567']));

    expect(PanoptesTranscription::count())->toBe(0)
        ->and(importErrors($this->service)[0]['error'])->toBe('Could not find subject id for classification');
});

it('records an error for a row without a subject id', function (?string $subjectId) {
    importRow($this->service, transcriptRow($this->subject, ['subject_subjectId' => $subjectId]));

    expect(PanoptesTranscription::count())->toBe(0)
        ->and(importErrors($this->service)[0]['error'])->toBe('Transcript missing subject id');
})->with(['missing' => [null], 'empty' => [''], 'blank' => ['  ']]);

it('records an error when a row has a different number of columns than the header', function () {
    $row = transcriptRow($this->subject);

    $this->service->processRow([...array_keys($row), 'extra_column'], $row, 12);

    expect(PanoptesTranscription::count())->toBe(0)
        ->and(importErrors($this->service)[0]['error'])->toContain('Header column count does not match row count');
});
