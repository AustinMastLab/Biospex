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

beforeEach(function () {
    PanoptesTranscription::truncate();
});

afterEach(function () {
    PanoptesTranscription::truncate();
});

it('stores the contributor and transcription counts, leaving out not-logged-in names', function () {
    foreach (['alice', 'alice', 'bob', 'not-logged-in-1a2b', 'Not-Logged-In-3c4d'] as $userName) {
        PanoptesTranscription::create(['user_name' => $userName]);
    }

    $this->artisan('app:update-site-counts')->assertSuccessful();

    $service = app(PanoptesTranscriptionService::class);
    expect($service->getContributorCount())->toBe(2)
        ->and($service->getTotalTranscriptions())->toBe(5);
});

it('keeps the previous counts when counting fails', function () {
    Cache::store(config('config.site_counts_cache_store'))
        ->forever(PanoptesTranscriptionService::SITE_COUNTS_KEY, ['contributors' => 16269, 'transcriptions' => 1989247]);
    $failing = Mockery::mock(PanoptesTranscriptionService::class, [new PanoptesTranscription])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $failing->shouldReceive('countContributors')->andThrow(new RuntimeException('operation exceeded time limit'));
    app()->instance(PanoptesTranscriptionService::class, $failing);

    expect(fn () => $this->artisan('app:update-site-counts')->run())
        ->toThrow(RuntimeException::class, 'operation exceeded time limit');

    expect($failing->getContributorCount())->toBe(16269)
        ->and($failing->getTotalTranscriptions())->toBe(1989247);
});
