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

use App\Models\ExportQueue;
use App\Traits\NotifyOnJobFailure;
use App\Traits\ResolvesImageQueue;
use Aws\Sqs\SqsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Process images for Zooniverse export by sending them to SQS queue for processing.
 */
class ZooniverseExportProcessImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotifyOnJobFailure, Queueable, ResolvesImageQueue, SerializesModels;

    public int $timeout = 1800;

    /**
     * Create a new job instance.
     *
     * @param  ExportQueue  $exportQueue  The export queue model instance
     */
    public function __construct(protected ExportQueue $exportQueue)
    {
        $this->exportQueue = $exportQueue->withoutRelations();
        $this->onQueue(config('config.queue.export'));
    }

    /**
     * Execute the job to process images for Zooniverse export.
     * Sends unprocessed files to SQS queue for processing.
     *
     * @param  SqsClient  $sqs  AWS SQS client instance
     *
     * @throws \Exception When no unprocessed files are found
     */
    public function handle(SqsClient $sqs): void
    {
        $this->exportQueue->load('expedition');

        // Count files first for logging/logic
        $totalFiles = $this->exportQueue->files()->where('processed', 0)->count();

        // If no files → do nothing
        if ($totalFiles === 0) {
            throw new \Exception("No unprocessed files found for export queue ID: {$this->exportQueue->id}");
        }

        $sentCount = 0;

        $updatesQueueUrl = $sqs->getQueueUrl([
            'QueueName' => config('services.aws.sqs.export_update'),
        ])['QueueUrl'];

        $processDir = "{$this->exportQueue->id}-".config('zooniverse.actor_id')."-{$this->exportQueue->expedition->uuid}";
        $s3Bucket = config('filesystems.disks.s3.bucket');

        // Cache resolved queue URLs per host to avoid repeated SQS API calls
        $queueUrlCache = [];

        // Use chunking to save memory and by id to lock rows
        $this->exportQueue->files()
            ->where('processed', 0)
            ->orderBy('id')
            ->chunkById(1000, function ($files) use ($sqs, $updatesQueueUrl, $processDir, $s3Bucket, &$sentCount, &$queueUrlCache) {
                // Group messages by destination queue URL (archive.org vs standard)
                $batches = [];

                foreach ($files as $file) {
                    $host = parse_url($file->access_uri, PHP_URL_HOST) ?? '';

                    if (! isset($queueUrlCache[$host])) {
                        $queueUrlCache[$host] = $this->resolveImageQueueUrl($sqs, $file->access_uri);
                    }

                    $batches[$queueUrlCache[$host]][] = [
                        'Id' => (string) $file->id,
                        'MessageBody' => json_encode([
                            'taskType'        => 'export',
                            'queueId'         => $this->exportQueue->id,
                            'fileId'          => $file->id,
                            'subjectId'       => $file->subject_id,
                            'accessURI'       => $file->access_uri,
                            's3Bucket'        => $s3Bucket,
                            's3Path'          => "scratch/{$processDir}/{$file->subject_id}.jpg",
                            'updatesQueueUrl' => $updatesQueueUrl,
                            'maxWidth'        => 1500,
                            'maxHeight'       => 1500,
                        ]),
                    ];
                }

                // Send in chunks of 10 per queue (SQS batch limit)
                foreach ($batches as $queueUrl => $messages) {
                    foreach (array_chunk($messages, 10) as $chunk) {
                        $sqs->sendMessageBatch([
                            'QueueUrl' => $queueUrl,
                            'Entries'  => $chunk,
                        ]);
                        $sentCount += count($chunk);
                    }
                }
            }, 'id');

        // Check if all messages were sent successfully
        if ($sentCount !== $totalFiles) {
            \Artisan::queue('app:lambda-control', [
                'lambda' => config('services.aws.lambdas.BiospexImageFetcher'),
                'action' => 'stop',
            ])->onQueue(config('config.queue.default'));
            throw new \Exception("SQS send incomplete: {$sentCount}/{$totalFiles} messages sent");
        }
    }

    /**
     * Handle job failure by updating export queue and notifying admin.
     *
     * @param  Throwable  $throwable  The exception that caused the failure
     */
    public function failed(Throwable $throwable): void
    {
        $this->exportQueue->error = 1;
        $this->exportQueue->save();

        $this->notifyGroupOnFailure($this->exportQueue, $throwable);
    }
}
