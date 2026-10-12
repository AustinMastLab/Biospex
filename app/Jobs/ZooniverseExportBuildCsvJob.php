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
use App\Models\ExportQueue;
use App\Models\ExportQueueFile;
use App\Services\Actor\Zooniverse\ZooniverseZipTriggerService;
use App\Services\Csv\AwsS3CsvService;
use App\Services\Process\MapZooniverseCsvColumnsService;
use App\Traits\NotifyOnJobFailure;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Job to build CSV manifest file for Zooniverse export.
 *
 * This job processes exported images from S3, creates a CSV manifest file,
 * and triggers the zip creation process. It handles:
 * - Listing and counting S3 objects
 * - Building CSV with mapped columns
 * - Validating row counts
 * - Triggering zip creation via SQS
 */
class ZooniverseExportBuildCsvJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, NotifyOnJobFailure, Queueable, SerializesModels;

    public int $timeout = 3600;

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
     * Execute the job.
     *
     * Processes S3 objects, creates CSV manifest, and triggers zip creation.
     *
     * @param  AwsS3CsvService  $awsS3CsvService  Service for handling S3 CSV operations
     * @param  MapZooniverseCsvColumnsService  $mapZooniverseCsvColumnsService  Service for mapping CSV columns
     *
     * @throws \Exception When no images are found or CSV row count doesn't match the image count
     */
    public function handle(
        AwsS3CsvService $awsS3CsvService,
        MapZooniverseCsvColumnsService $mapZooniverseCsvColumnsService,
        ZooniverseZipTriggerService $zipTriggerService
    ): void {

        $this->exportQueue->load('expedition');

        // === GET ALL EXPORT DATA ===
        $exportData = $zipTriggerService->getExportData($this->exportQueue);

        // === CHECK AND DELETE EXISTING MANIFEST.CSV ===
        $zipTriggerService->deleteManifest($exportData);

        // === BUILD CSV ON S3 ===
        $awsS3CsvService->createBucketStream($exportData['s3Bucket'], $exportData['csvFilePath'], 'w');
        $awsS3CsvService->createCsvWriterFromStream();
        $awsS3CsvService->csv->addEncodingFormatter();

        $first = true;
        $csvRowCount = 0;

        ExportQueueFile::where('queue_id', $this->exportQueue->id)
            ->chunkById(1000, function ($chunk) use (
                $exportData,
                $awsS3CsvService,
                $mapZooniverseCsvColumnsService,
                &$first,
                &$csvRowCount
            ) {
                $csvData = $chunk->filter(fn ($file) => in_array("{$exportData['fullProcessPath']}/{$file->subject_id}.jpg", $exportData['imageKeys'])
                )->map(fn ($file) => $mapZooniverseCsvColumnsService->mapColumns($file, $this->exportQueue)
                );

                if ($csvData->isEmpty()) {
                    return;
                }

                if ($first) {
                    $awsS3CsvService->csv->insertOne(array_keys((array) $csvData->first()));
                    $first = false;
                }

                $awsS3CsvService->csv->insertAll($csvData->toArray());
                $csvRowCount += $csvData->count();
            });

        // === VALIDATE ROW COUNT ===
        if ($csvRowCount !== $exportData['fileCount']) {
            throw new \Exception(t('CSV row count (%s) does not match image count (%s).', $csvRowCount, $exportData['fileCount']));
        }

        // === SEND ZIP TRIGGER ===
        $zipTriggerService->sendZipTrigger($this->exportQueue, $exportData['totalSize'], $exportData['fileCount']);

        $this->exportQueue->stage = ExportQueueStage::CreatingArchive;
        $this->exportQueue->save();
    }

    /**
     * Handle a job failure.
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
