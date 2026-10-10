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
use App\Models\Expedition;
use App\Models\PanoptesProject;
use App\Models\PanoptesTranscription;
use App\Models\PusherTranscription;
use App\Services\Transcriptions\UpdateOrCreatePusherTranscriptionService;
use Carbon\Carbon;

beforeEach(function () {
    PanoptesTranscription::truncate();
    PusherTranscription::truncate();

    $expedition = Expedition::factory()->create();
    PanoptesProject::factory()->create(['expedition_id' => $expedition->id, 'title' => 'Herbarium Ledgers']);
    // Loaded from the database, as the queued job gets it (uuid is then a string, not a Uuid object).
    $this->expedition = Expedition::with('panoptesProject')->find($expedition->id);
    $this->service = app(UpdateOrCreatePusherTranscriptionService::class);
});

afterEach(function () {
    PanoptesTranscription::truncate();
    PusherTranscription::truncate();
});

/**
 * A Panoptes transcription imported from the expedition's CSV, with encoded field names.
 */
function csvTranscription(Expedition $expedition, array $fields = []): PanoptesTranscription
{
    $encoded = collect(array_merge(['Country' => 'United States', 'County' => 'Alachua', 'Location' => 'Gainesville'], $fields))
        ->mapWithKeys(fn ($value, $field) => [TranscriptionMapHelper::encodeTranscriptionField($field) => $value])
        ->all();

    return PanoptesTranscription::create(array_merge([
        'classification_id' => 778691035,
        'subject_expeditionId' => $expedition->id,
        'subject_projectId' => $expedition->project_id,
        'subject_references' => 'https://www.zooniverse.org/subjects/1',
        'subject_accessURI' => 'https://images.example.org/1.jpg',
        'user_name' => 'volunteer',
        'classification_finished_at' => Carbon::parse('2026-10-08 12:00:00', 'UTC'),
    ], $encoded));
}

/**
 * A Pusher transcription as the live Pusher listener records it.
 *
 * @param  array<string, string>  $content
 */
function liveTranscription(array $content = [], array $attributes = []): void
{
    PusherTranscription::create(array_merge([
        'classification_id' => 778691035,
        'subject' => ['link' => 'https://www.zooniverse.org/subjects/1', 'thumbnailUri' => ''],
        'contributor' => ['transcriber' => '', 'ipAddress' => '203.0.113.7'],
        'transcriptionContent' => array_merge(['country' => '', 'county' => '', 'locality' => '', 'province' => '', 'collector' => '', 'taxon' => ''], $content),
    ], $attributes));
}

it('creates a Pusher transcription for a new classification', function () {
    $this->service->processTranscripts(csvTranscription($this->expedition), $this->expedition);

    $pusher = PusherTranscription::where('classification_id', 778691035)->sole();

    expect($pusher->expedition_uuid)->toBe((string) $this->expedition->uuid)
        ->and($pusher->project)->toBe('Herbarium Ledgers')
        ->and($pusher->contributor['transcriber'])->toBe('volunteer')
        ->and($pusher->subject['thumbnailUri'])->toBe('https://images.example.org/1.jpg')
        ->and($pusher->transcriptionContent['country'])->toBe('United States')
        ->and($pusher->transcriptionContent['county'])->toBe('Alachua');
});

it('updates a classification the live Pusher listener already recorded instead of duplicating it', function () {
    liveTranscription();

    $this->service->processTranscripts(csvTranscription($this->expedition), $this->expedition);

    $pusher = PusherTranscription::where('classification_id', 778691035)->sole();

    expect($pusher->contributor)->toBe(['transcriber' => 'volunteer', 'ipAddress' => '203.0.113.7'])
        ->and($pusher->subject['thumbnailUri'])->toBe('https://images.example.org/1.jpg')
        ->and($pusher->transcriptionContent['country'])->toBe('United States')
        ->and($pusher->transcriptionContent['county'])->toBe('Alachua')
        ->and($pusher->transcriptionContent['locality'])->toBe('Gainesville');
});

it('keeps what the live listener recorded when the CSV row has no value for it', function () {
    liveTranscription(['country' => 'Canada', 'province' => 'Ontario', 'taxon' => 'Quercus alba']);

    $this->service->processTranscripts(csvTranscription($this->expedition, ['Country' => '']), $this->expedition);

    expect(PusherTranscription::where('classification_id', 778691035)->sole()->transcriptionContent)
        ->country->toBe('Canada')
        ->province->toBe('Ontario')
        ->taxon->toBe('Quercus alba');
});

it('fills in the expedition for a classification the live listener recorded without one', function () {
    liveTranscription();

    $this->service->processTranscripts(csvTranscription($this->expedition), $this->expedition);

    expect(PusherTranscription::where('classification_id', 778691035)->sole()->expedition_uuid)->toBe((string) $this->expedition->uuid);
});
