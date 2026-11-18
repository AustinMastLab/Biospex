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

use App\Models\PanoptesTranscription;
use App\Services\Transcriptions\PanoptesTranscriptionService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    PanoptesTranscription::truncate();
});

afterEach(function () {
    PanoptesTranscription::truncate();
});

/**
 * A transcription by the given participant in a project.
 */
function participantTranscription(int $projectId, string $userName): void
{
    PanoptesTranscription::create([
        'subject_projectId' => $projectId,
        'subject_expeditionId' => 31,
        'user_name' => $userName,
        'classification_finished_at' => Carbon::parse('2024-01-01', 'UTC'),
    ]);
}

it('replaces the stored participant counts with current ones', function () {
    $service = app(PanoptesTranscriptionService::class);
    participantTranscription(7, 'fern_fan');
    $service->getTranscribersTranscriptionCount(7);
    participantTranscription(7, 'fern_fan');
    participantTranscription(7, 'moss_maven');

    $this->artisan('app:update-participant-counts')
        ->expectsOutput('Updated participant counts for 1 of 1 projects.')
        ->assertSuccessful();

    expect($service->getTranscribersTranscriptionCount(7)->pluck('transcriptionCount', 'user_name')->sortKeys()->all())
        ->toBe(['fern_fan' => 2, 'moss_maven' => 1]);
});

it('keeps the previous counts of a project whose count fails and updates the rest', function () {
    participantTranscription(7, 'fern_fan');
    participantTranscription(8, 'moss_maven');
    $failing = Mockery::mock(PanoptesTranscriptionService::class, [new PanoptesTranscription])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $failing->getTranscribersTranscriptionCount(7);
    participantTranscription(7, 'oak_lover');
    participantTranscription(8, 'oak_lover');
    $failing->shouldReceive('countProjectParticipants')->with(7)->andThrow(new RuntimeException('operation exceeded time limit'));
    $failing->shouldReceive('countProjectParticipants')->with(8)->passthru();
    app()->instance(PanoptesTranscriptionService::class, $failing);

    $this->artisan('app:update-participant-counts')
        ->expectsOutput('Updated participant counts for 1 of 2 projects.')
        ->assertFailed();

    expect($failing->getTranscribersTranscriptionCount(7)->pluck('user_name')->all())->toBe(['fern_fan'])
        ->and($failing->getTranscribersTranscriptionCount(8)->pluck('user_name')->sort()->values()->all())->toBe(['moss_maven', 'oak_lover']);
});
