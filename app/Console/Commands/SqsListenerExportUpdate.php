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

namespace App\Console\Commands;

use App\Jobs\ZooniverseExportImageUpdateJob;
use App\Jobs\ZooniverseExportZipResultJob;
use App\Models\ExportQueue;
use App\Services\SqsListenerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SqsListenerExportUpdate extends Command
{
    /** @var string Command signature */
    protected $signature = 'export:listen';

    /** @var string Command description */
    protected $description = 'Robust SQS listener for export update queue with reconnections and alerts';

    public function __construct(protected SqsListenerService $service)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        try {
            $this->validateConfiguration();
            $this->runWorker();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->service->handleCriticalError('Failed to start export updates listener', $e, $this);

            return self::FAILURE;
        }
    }

    /**
     * Validate required AWS configuration settings.
     *
     * @throws \RuntimeException When the required configuration is missing
     */
    private function validateConfiguration(): void
    {
        $required = [
            'services.aws.sqs.export_update',
            'services.aws.region',
        ];

        foreach ($required as $key) {
            if (empty(Config::get($key))) {
                throw new RuntimeException("Missing configuration: {$key}");
            }
        }
    }

    /**
     * Run the worker loop.
     */
    private function runWorker(): void
    {
        $idleChecker = fn () => $this->hasActiveExports();
        $queueKey = 'export_update';
        $graceKey = 'services.aws.export_idle_grace';
        $routeCallback = fn ($body) => $this->routeMessage($body);

        $this->service->run($idleChecker, $queueKey, $graceKey, $routeCallback, $this);
    }

    /**
     * Route message to appropriate job based on function name.
     *
     * @param  array  $data  Message data
     *
     * @throws \InvalidArgumentException|\Throwable When function is missing or unknown
     */
    private function routeMessage(array $data): void
    {
        if (! isset($data['function'])) {
            throw new \InvalidArgumentException('Message missing required "function" field');
        }

        $function = $data['function'];
        $status   = $data['status'] ?? 'unknown';
        $id       = $data['fileId'] ?? $data['subjectId'] ?? $data['queueId'] ?? '?';

        $this->info("[export:listen] Received: function={$function} status={$status} id={$id}");

        try {
            match ($function) {
                'BiospexImageProcess' => $this->dispatchImageProcessJob($data),
                'BiospexZipCreator', 'BiospexZipMerger' => $this->dispatchZipCreatorJob($data),
                default => throw new \InvalidArgumentException("Unknown function: {$function}"),
            };
        } catch (Throwable $e) {
            Log::error('Failed to dispatch job', [
                'function' => $function,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch an image process job based on message data.
     *
     * @param  array  $data  Message data containing image processing results
     *
     * @throws \InvalidArgumentException When required fields are missing
     */
    private function dispatchImageProcessJob(array $data): void
    {
        $status = $data['status'] ?? throw new \InvalidArgumentException('Missing status');

        // Allow either fileId or subjectId for lookup flexibility
        if (! isset($data['fileId']) && ! isset($data['subjectId'])) {
            throw new \InvalidArgumentException('Missing both fileId and subjectId');
        }

        $id = $data['fileId'] ?? $data['subjectId'];

        // Dispatch the job for BOTH success and failure
        ZooniverseExportImageUpdateJob::dispatch($data);

        if ($status === 'failed') {
            $error = $data['error'] ?? 'Unknown error';
            $this->error("[export:listen] Image FAILED #{$id}: {$error}");
            Log::error("Image processing failed for image #{$id}: {$error}", $data);
        } else {
            $this->line("[export:listen] Image OK #{$id} → dispatched ZooniverseExportImageUpdateJob");
        }
    }

    /**
     * Dispatch a zip creator job based on message data.
     *
     * @param  array  $data  Message data containing zip processing results
     *
     * @throws \InvalidArgumentException When required fields are missing
     * @throws \RuntimeException When zip processing failed
     */
    private function dispatchZipCreatorJob(array $data): void
    {
        $status = $data['status'] ?? throw new \InvalidArgumentException('Missing status');
        $queueId = $data['queueId'] ?? throw new \InvalidArgumentException('Missing queueId');

        if ($status === 'zip-failed') {
            $error = $data['error'] ?? 'Unknown error';

            // Ignore harmless empty-batch noise
            if (str_contains($error, 'No files found')) {
                // This is a normal empty-batch message from the Step Function — ignore it

                return;   // ← do NOT throw, do NOT dispatch a job
            }

            // REAL FAILURE — update DB and re-throw so handleError() sends the email
            Log::error('BiospexZipCreator failed', $data);

            ExportQueue::where('id', $queueId)->update([
                'error' => 1,
                'error_message' => $error,
            ]);

            // Send email with the error message
            throw new RuntimeException("Zip export failed for export #{$queueId}: {$error}");
        }

        // Don't proceed if the data is from batching large export.
        if ($status === 'partial-zip-ready') {
            return;
        }

        ZooniverseExportZipResultJob::dispatch($data);
    }

    /**
     * Check if the export queue has pending exports.
     *
     * @return bool True if exports > 0
     */
    private function hasActiveExports(): bool
    {
        return ExportQueue::where('queued', 1)->where('error', 0)->count() > 0;
    }
}
