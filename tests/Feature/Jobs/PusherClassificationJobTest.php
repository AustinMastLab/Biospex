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

use App\Jobs\PusherClassificationJob;
use App\Jobs\PusherTranscriptionJob;
use App\Services\Api\PanoptesApiService;
use Illuminate\Support\Facades\Queue;

it('still saves the classification when the Panoptes user lookup fails', function () {
    Queue::fake([PusherTranscriptionJob::class]);
    test()->mock(PanoptesApiService::class, function ($mock) {
        $mock->shouldReceive('getPanoptesSubject')->with(55)->andReturn(['metadata' => ['scientificName' => 'Quercus alba']]);
        $mock->shouldReceive('getPanoptesUser')->with(3103581)->andThrow(new RuntimeException('502 Bad Gateway'));
    });

    PusherClassificationJob::dispatchSync([
        'classification_id' => '1001',
        'subject_ids' => [55],
        'user_id' => 3103581,
    ], 'Notes From Nature');

    Queue::assertPushed(PusherTranscriptionJob::class, 1);
});
