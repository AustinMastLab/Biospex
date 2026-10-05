<?php

/*
 * Copyright (C) 2014 - 2025, Biospex
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

namespace App\Services\Actor\TesseractOcr;

use App\Jobs\TesseractOcrCompleteJob;
use App\Jobs\TesseractOcrProcessJob;
use App\Models\OcrQueue;
use App\Services\Api\AwsLambdaApiService;

/**
 * Service for managing OCR queue processing using Tesseract OCR via AWS Lambda.
 * Handles queue processing, status management, and Lambda availability checks.
 */
class TesseractOcrQueueService
{
    /**
     * Create a new TesseractOcrQueueService instance.
     *
     * @param  OcrQueue  $ocrQueue  The OCR queue model instance
     * @param  AwsLambdaApiService  $lambdaApiService  Checks whether the OCR Lambdas can run
     */
    public function __construct(
        protected OcrQueue $ocrQueue,
        protected AwsLambdaApiService $lambdaApiService
    ) {}

    /**
    /**
     * Check for the active queue to see if it has finished processing.
     */
    public function checkActiveQueuesForCompletion(): void
    {
        // Find the single active, error-free queue currently in progress
        $queue = $this->ocrQueue
            ->where('queued', 1)
            ->where('stage', 1)
            ->where('error', 0)
            ->first();

        // If no active queue exists, there's nothing to check
        if (! $queue) {
            return;
        }

        // Check if all files are processed
        $isDone = ! $queue->files()->where('processed', 0)->exists();

        if ($isDone) {
            TesseractOcrCompleteJob::dispatch($queue);
        }
    }

    /**
     * Process the next queue item in the OCR queue.
     *
     * @param  bool  $reset  Whether to reset and process from the first queue item
     *
     * @throws \Exception
     */
    public function processNextQueue(bool $reset = false): void
    {
        if ($this->ocrQueue->where('queued', 1)->where('error', 0)->exists()) {
            return; // Already running one
        }

        // Find the ID of the next candidate
        $nextQueue = $this->getNextQueue($reset);
        if (! $nextQueue) {
            return;
        }

        // ATOMIC LOCK: Try to update queued=1 WHERE id=X AND queued=0
        // This returns 1 if successful, 0 if someone else grabbed it first.
        $affected = $this->ocrQueue
            ->where('id', $nextQueue->id)
            ->where('queued', 0) // Critical check
            ->update(['queued' => 1, 'error' => 0]);

        if ($affected === 0) {
            // Someone else claimed it in the last millisecond. Abort.
            return;
        }

        // Reload the model to ensure we have a fresh state if needed, though ID is enough
        $queue = $this->ocrQueue->find($nextQueue->id);

        // OCR needs both the image fetcher and the OCR processor.
        if (! $this->lambdaApiService->canRun(['BiospexImageFetcher', 'BiospexOcrProcessor'])) {
            // Rollback the claim if lambda fails
            $queue->queued = 0;
            $queue->save();
            throw new \Exception("OCR Lambda concurrency is 0 — skipping queue #{$queue->id}");
        }

        TesseractOcrProcessJob::dispatch($queue);
    }

    /**
     * Retrieve the next queue item to be processed.
     *
     * @param  bool  $reset  Whether to reset and get the first queue item
     * @return OcrQueue|null The next queue item or null if none available
     */
    private function getNextQueue(bool $reset): ?OcrQueue
    {
        if ($reset) {
            return $this->ocrQueue->orderBy('id')->first();
        }

        return $this->ocrQueue
            ->where('queued', 0)
            ->where('error', 0)
            ->where('files_ready', 1)
            ->orderBy('id')
            ->first();
    }
}
