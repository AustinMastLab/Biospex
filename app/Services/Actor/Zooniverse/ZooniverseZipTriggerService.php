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

namespace App\Services\Actor\Zooniverse;

use App\Models\ExportQueue;
use Aws\S3\S3Client;
use Aws\Sfn\SfnClient;
use Aws\Sqs\SqsClient;

/**
 * Service for handling Zooniverse ZIP trigger operations via AWS SQS.
 */
class ZooniverseZipTriggerService
{
    public function __construct(
        protected SfnClient $stepFunctions,
        protected SqsClient $sqs,
        protected S3Client $s3
    ) {}

    /**
     * Get complete export data including paths and S3 file information.
     *
     * @return array [totalSize, fileCount, imageKeys, fullProcessPath, csvFilePath, s3Bucket]
     *
     * @throws \Exception When no images are found
     */
    public function getExportData(ExportQueue $exportQueue): array
    {
        // Build all paths
        $scratchDir = config('config.scratch_dir');
        $processDir = $this->buildProcessDir($exportQueue);
        $fullProcessPath = "{$scratchDir}/{$processDir}";
        $csvFilePath = "{$fullProcessPath}/manifest.csv";
        $s3Bucket = config('filesystems.disks.s3.bucket');

        // Get S3 file data
        $totalSize = 0;
        $fileCount = 0;
        $imageKeys = [];

        $paginator = $this->s3->getPaginator('ListObjectsV2', [
            'Bucket' => $s3Bucket,
            'Prefix' => "{$fullProcessPath}/",
        ]);

        foreach ($paginator as $page) {
            foreach ($page['Contents'] ?? [] as $object) {
                if (str_ends_with($object['Key'], '.jpg')) {
                    $totalSize += $object['Size'];
                    $fileCount++;
                    $imageKeys[] = $object['Key'];
                }
            }
        }

        if ($fileCount === 0) {
            throw new \Exception("No images found in S3 directory: {$fullProcessPath}");
        }

        return [
            'totalSize' => $totalSize,
            'fileCount' => $fileCount,
            'imageKeys' => $imageKeys,
            'fullProcessPath' => $fullProcessPath,
            'csvFilePath' => $csvFilePath,
            's3Bucket' => $s3Bucket,
        ];
    }

    /**
     * Send ZIP trigger message to AWS SQS queue.
     *
     * @param  ExportQueue  $exportQueue  The export queue instance
     * @param  int  $totalSize  Total file size in bytes
     * @param  int  $fileCount  Number of files
     *
     * @throws \Exception When unable to send message
     */
    public function sendZipTrigger(ExportQueue $exportQueue, int $totalSize, int $fileCount): void
    {
        // Get queue URLs
        $queueUrl = $this->getQueueUrl('export_zip_trigger');
        $updatesQueueUrl = $this->getQueueUrl('export_update');

        // Build process directory path
        $processDir = $this->buildProcessDir($exportQueue);

        // Prepare common payload
        $payload = [
            'processDir' => $processDir,
            's3Bucket' => config('filesystems.disks.s3.bucket'),
            'updatesQueueUrl' => $updatesQueueUrl,
            'queueId' => $exportQueue->id,
            'totalSize' => $totalSize,
            'fileCount' => $fileCount,
            'finalKey' => "export/{$processDir}.zip", // Added for Step Function
        ];

        // Configurable threshold (e.g., from .env or config)
        $zipThreshold = config('services.aws.zip_threshold');

        if ($fileCount > $zipThreshold) {
            // Trigger Step Function for large jobs
            $this->stepFunctions->startExecution([
                'stateMachineArn' => config('services.aws.zip_state_machine_arn'),
                'input' => json_encode($payload),
            ]);
        } else {
            // Send to SQS for direct Lambda processing for small jobs
            $this->sqs->sendMessage([
                'QueueUrl' => $queueUrl,
                'MessageBody' => json_encode($payload),
            ]);
        }
    }

    /**
     * Process complete ZIP trigger workflow: get export data and send trigger.
     *
     * @return array Export data for other uses
     *
     * @throws \Exception
     */
    public function processZipTrigger(ExportQueue $exportQueue): array
    {
        $exportData = $this->getExportData($exportQueue);

        $this->sendZipTrigger($exportQueue, $exportData['totalSize'], $exportData['fileCount']);

        return $exportData;
    }

    /**
     * Delete manifest CSV file from S3 bucket if it exists.
     *
     * @param  array  $exportData  Export data containing s3Bucket and csvFilePath keys
     */
    public function deleteManifest(array $exportData): void
    {
        if ($this->s3->doesObjectExist($exportData['s3Bucket'], $exportData['csvFilePath'])) {
            $this->s3->deleteObject([
                'Bucket' => $exportData['s3Bucket'],
                'Key' => $exportData['csvFilePath'],
            ]);
        }
    }

    /**
     * Build the process directory name for the export queue.
     */
    protected function buildProcessDir(ExportQueue $exportQueue): string
    {
        return "{$exportQueue->id}-".config('zooniverse.actor_id')."-{$exportQueue->expedition->uuid}";
    }

    /**
     * Get AWS SQS queue URL for given queue key.
     *
     * @param  string  $key  Queue configuration key
     * @return string Queue URL
     */
    protected function getQueueUrl(string $key): string
    {
        $queueName = config("services.aws.sqs.{$key}");

        return $this->sqs->getQueueUrl(['QueueName' => $queueName])['QueueUrl'];
    }
}
