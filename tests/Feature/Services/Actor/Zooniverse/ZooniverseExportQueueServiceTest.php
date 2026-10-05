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

use App\Jobs\ZooniverseExportProcessImagesJob;
use App\Models\ExportQueue;
use App\Services\Actor\Zooniverse\ZooniverseExportQueueService;
use App\Services\Api\AwsLambdaApiService;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->exportQueue = ExportQueue::factory()->create(['queued' => 0, 'stage' => 0, 'error' => 0, 'files_ready' => 1]);
});

it('leaves the next export queue waiting while the image fetcher is paused', function () {
    mock(AwsLambdaApiService::class)
        ->shouldReceive('canRun')
        ->once()
        ->with(['BiospexImageFetcher'])
        ->andReturnFalse();
    Queue::fake();

    expect(fn () => app(ZooniverseExportQueueService::class)->processNextQueue())
        ->toThrow(Exception::class, "Export Lambda concurrency is 0 — skipping queue #{$this->exportQueue->id}");

    expect($this->exportQueue->fresh())
        ->queued->toBe(0)
        ->stage->toBe(0);
    Queue::assertNothingPushed();
});

it('starts the listeners and dispatches the next export queue when the image fetcher can run', function () {
    mock(AwsLambdaApiService::class)
        ->shouldReceive('canRun')
        ->once()
        ->with(['BiospexImageFetcher'])
        ->andReturnTrue();
    Queue::fake();

    app(ZooniverseExportQueueService::class)->processNextQueue();

    expect($this->exportQueue->fresh())
        ->queued->toBe(1)
        ->stage->toBe(1);
    Queue::assertPushed(QueuedCommand::class, 1);
    Queue::assertPushed(ZooniverseExportProcessImagesJob::class, 1);
    Queue::assertCount(2);
});
