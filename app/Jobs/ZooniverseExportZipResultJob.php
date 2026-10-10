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

namespace App\Jobs;

use App\Enums\ExportQueueStage;
use App\Enums\LambdaStatus;
use App\Models\Download;
use App\Models\ExportQueue;
use App\Models\User;
use App\Notifications\Generic;
use App\Traits\NotifyOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Handles the result of a Zooniverse export ZIP operation.
 *
 * This job processes the results of a ZIP operation for exported Zooniverse data,
 * managing success and failure scenarios, creating download records, and dispatching
 * report generation jobs as needed.
 */
class ZooniverseExportZipResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotifyOnJobFailure, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array  $data  Array containing queue ID and status information for the ZIP operation
     */
    public function __construct(
        public array $data
    ) {
        $this->onQueue(config('config.queue.export'));
    }

    /**
     * Execute the job.
     *
     * Processes the ZIP operation result, handling both success and failure scenarios.
     *
     * @throws \Exception When payload is invalid or queue record is not found
     */
    public function handle(): void
    {
        $queueId = $this->data['queueId'] ?? null;
        $status = $this->data['status'] ?? null;

        if (! $queueId || ! $status) {
            throw new \Exception('Invalid ZIP result payload: '.json_encode($this->data));
        }

        $queue = ExportQueue::with(['expedition'])->find($queueId);
        if (! $queue) {
            throw new \Exception("ExportQueue #{$queueId} not found");
        }

        if (LambdaStatus::tryFrom((string) $status) === LambdaStatus::ZipReady) {
            $this->handleZipSuccess($queue);
        } else {
            $this->handleZipFailure($queue, $this->data);
        }
    }

    /**
     * Handle a successful ZIP operation.
     *
     * Updates queue stage, creates download record and dispatches report generation.
     *
     * @param  ExportQueue  $queue  The export queue records to update
     */
    private function handleZipSuccess(ExportQueue $queue): void
    {
        $queue->stage = ExportQueueStage::CreatingReport;
        $queue->save();

        // === CREATE DOWNLOAD RECORD ===
        $this->createDownloadRecord($queue);

        ZooniverseExportCreateReportJob::dispatch($queue);
    }

    /**
     * Handle failed ZIP operation.
     *
     * Marks the queue as errored and throws an exception.
     *
     * @param  ExportQueue  $queue  The export queue records to update
     * @param  array  $data  The failure data
     *
     * @throws \Exception When the ZIP operation fails
     */
    private function handleZipFailure(ExportQueue $queue, array $data): void
    {
        $queue->error = 1;
        $queue->save();

        $errorMessage = "ZIP failed for queue #{$queue->id}";
        if (! empty($data)) {
            $errorMessage .= '. Additional data: '.json_encode($data);
        }

        throw new \Exception($errorMessage, 0);
    }

    /**
     * Create or update download record for the exported ZIP file.
     *
     * @param  ExportQueue  $exportQueue  The export queue records to create download for
     */
    private function createDownloadRecord(ExportQueue $exportQueue): void
    {
        $file = "{$exportQueue->id}-".config('zooniverse.actor_id')."-{$exportQueue->expedition->uuid}.zip";
        $values = [
            'expedition_id' => $exportQueue->expedition_id,
            'actor_id' => $exportQueue->actor_id,
            'file' => $file,
            'type' => 'export',
        ];
        $attributes = [
            'expedition_id' => $exportQueue->expedition_id,
            'actor_id' => $exportQueue->actor_id,
            'file' => $file,
            'type' => 'export',
        ];

        Download::updateOrCreate($attributes, $values);
    }

    /**
     * Handle a job failure.
     *
     * Updates the queue status and notifies the admin user about the failure.
     *
     * @param  Throwable  $throwable  The exception that caused the failure
     */
    public function failed(Throwable $throwable): void
    {
        $queueId = $this->data['queueId'] ?? null;
        $queue = $queueId ? ExportQueue::find($queueId) : null;

        if ($queue) {
            $queue->error = 1;
            $queue->save();
            $this->notifyGroupOnFailure($queue, $throwable);
        } else {
            // Fallback to admin
            $admin = User::find(config('config.admin.user_id'));
            $admin?->notify(new Generic([
                'subject' => t('Export ZIP Job Failed'),
                'html' => [t('Error: %s', $throwable->getMessage())],
            ]));
        }
    }
}
