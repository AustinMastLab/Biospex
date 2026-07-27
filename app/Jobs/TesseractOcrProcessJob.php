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

use App\Models\OcrQueue;
use App\Models\User;
use App\Notifications\Generic;
use App\Traits\ResolvesImageQueue;
use Aws\Sqs\SqsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Job to process OCR queue files using Tesseract OCR engine.
 * Sends unprocessed files to AWS SQS queue for OCR processing.
 */
class TesseractOcrProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ResolvesImageQueue, SerializesModels;

    public int $timeout = 1800;

    /**
     * Create a new job instance.
     *
     * @param  OcrQueue  $ocrQueue  The OCR queue to process
     */
    public function __construct(protected OcrQueue $ocrQueue)
    {
        $this->ocrQueue = $ocrQueue->withoutRelations();
        $this->onQueue(config('config.queue.ocr'));
    }

    /**
     * Execute the job.
     * Retrieves unprocessed files from OCR queue and sends them to AWS SQS for processing.
     *
     * @param  SqsClient  $sqs  AWS SQS client instance
     *
     * @throws \Exception
     */
    public function handle(SqsClient $sqs): void
    {
        // Count files first for logging/logic
        $totalFiles = $this->ocrQueue->files()->where('processed', 0)->count();

        // If no files → do nothing
        if ($totalFiles === 0) {
            throw new \Exception("No unprocessed files found for ocr queue ID: {$this->ocrQueue->id}");
        }

        // Unified call to start the OCR update listener and the shared DLQ listener
        \Artisan::queue('sqs:control ocr_update image_trigger_dlq --action=start')
            ->onQueue(config('config.queue.default'));

        $sentCount = 0;

        $updatesQueueUrl = $sqs->getQueueUrl([
            'QueueName' => config('services.aws.sqs.ocr_update'),
        ])['QueueUrl'];

        $ocrDir = config('zooniverse.directory.lambda-ocr-wip');
        $s3Bucket = config('filesystems.disks.s3.bucket');

        // Cache resolved queue URLs per host to avoid repeated SQS API calls
        $queueUrlCache = [];

        // Use chunking to save memory and by id to lock rows
        $this->ocrQueue->files()
            ->where('processed', 0)
            ->orderBy('id')
            ->chunkById(1000, function ($files) use ($sqs, $updatesQueueUrl, $ocrDir, $s3Bucket, &$sentCount, &$queueUrlCache) {
                // Group messages by destination queue URL (archive.org vs standard)
                $batches = [];

                foreach ($files as $file) {
                    $host = parse_url($file->access_uri, PHP_URL_HOST) ?? '';

                    if (! isset($queueUrlCache[$host])) {
                        $queueUrlCache[$host] = $this->resolveImageQueueUrl($sqs, $file->access_uri);
                    }

                    $s3Path = "{$ocrDir}/{$this->ocrQueue->id}/{$file->subject_id}.jpg";

                    $batches[$queueUrlCache[$host]][] = [
                        'Id' => (string) $file->id,
                        'MessageBody' => json_encode([
                            'taskType'        => 'ocr',
                            'queueId'         => $this->ocrQueue->id,
                            'fileId'          => $file->id,
                            'subjectId'       => $file->subject_id,
                            'accessURI'       => $file->access_uri,
                            's3Bucket'        => $s3Bucket,
                            's3Path'          => $s3Path,
                            'updatesQueueUrl' => $updatesQueueUrl,
                            'maxWidth'        => 2500,
                            'maxHeight'       => 2500,
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

        if ($sentCount !== $totalFiles) {
            \Artisan::queue('app:lambda-control', [
                'lambda' => config('services.aws.lambdas.BiospexImageFetcher'),
                'action' => 'stop',
            ])->onQueue(config('config.queue.default'));
            throw new \Exception("SQS send incomplete: {$sentCount}/{$totalFiles} messages sent");
        }

        $this->ocrQueue->stage = 1;
        $this->ocrQueue->save();

        $this->delete();
    }

    /**
     * Handle a job failure.
     * Marks OCR queue as error and sends notification to admin.
     *
     * @param  Throwable  $throwable  Exception that caused the failure
     */
    public function failed(Throwable $throwable): void
    {
        $this->ocrQueue->error = 1;
        $this->ocrQueue->save();

        $attributes = [
            'subject' => t('OCR Process Job Failed'),
            'html' => [
                t('OCR Queue ID: %s', $this->ocrQueue->id),
                t('Project ID: %s', $this->ocrQueue->project_id),
                t('Expedition ID: %s', $this->ocrQueue->expedition_id ?? 'None'),
                t('Error: %s', $throwable->getMessage()),
            ],
        ];

        $user = User::find(config('config.admin.user_id'));
        $user->notify(new Generic($attributes));
    }
}
