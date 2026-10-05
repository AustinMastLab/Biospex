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

use App\Jobs\TesseractOcrProcessJob;
use App\Models\OcrQueue;
use App\Services\Actor\TesseractOcr\TesseractOcrQueueService;
use App\Services\Api\AwsLambdaApiService;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->ocrQueue = OcrQueue::factory()->create(['queued' => 0, 'error' => 0, 'files_ready' => 1]);
});

it('leaves the next OCR queue waiting while the image fetcher or OCR processor is paused', function () {
    mock(AwsLambdaApiService::class)
        ->shouldReceive('canRun')
        ->once()
        ->with(['BiospexImageFetcher', 'BiospexOcrProcessor'])
        ->andReturnFalse();
    Queue::fake();

    expect(fn () => app(TesseractOcrQueueService::class)->processNextQueue())
        ->toThrow(Exception::class, "OCR Lambda concurrency is 0 — skipping queue #{$this->ocrQueue->id}");

    expect($this->ocrQueue->fresh()->queued)->toBe(0);
    Queue::assertNothingPushed();
});

it('dispatches the next OCR queue when the OCR Lambdas can run', function () {
    mock(AwsLambdaApiService::class)
        ->shouldReceive('canRun')
        ->once()
        ->with(['BiospexImageFetcher', 'BiospexOcrProcessor'])
        ->andReturnTrue();
    Queue::fake([TesseractOcrProcessJob::class]);

    app(TesseractOcrQueueService::class)->processNextQueue();

    expect($this->ocrQueue->fresh()->queued)->toBe(1);
    Queue::assertPushed(TesseractOcrProcessJob::class);
});
