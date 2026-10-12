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

use App\Enums\LambdaStatus;
use App\Jobs\ZooniverseExportBatchResultJob;
use App\Services\SqsListenerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Listens to the batch update queue for results from the BiospexBatchCreator Lambda.
 *
 * Messages are routed by their `function` field: `BiospexBatchCreator`.
 */
class SqsListenerBatchUpdate extends Command
{
    /** @var string Command signature */
    protected $signature = 'batch:listen';

    /** @var string Command description */
    protected $description = 'Robust SQS listener for batch update queue with reconnections and alerts';

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
            $this->service->handleCriticalError('Failed to start batch updates listener', $e, $this);

            return self::FAILURE;
        }
    }

    /**
     * Validate required AWS configuration settings.
     *
     * @throws RuntimeException When the required configuration is missing
     */
    private function validateConfiguration(): void
    {
        $required = [
            'services.aws.sqs.batch_update',
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
        $idleChecker = fn () => $this->hasActiveBatches();
        $queueKey = 'batch_update';
        $graceKey = 'services.aws.batch_idle_grace';
        $routeCallback = fn ($body) => $this->routeMessage($body);

        $this->service->run($idleChecker, $queueKey, $graceKey, $routeCallback, $this);
    }

    /**
     * Route message to the appropriate job based on the function name.
     *
     * @param  array  $data  Message data
     *
     * @throws InvalidArgumentException|Throwable When a function is missing or unknown
     */
    public function routeMessage(array $data): void
    {
        if (! isset($data['function'])) {
            throw new InvalidArgumentException('Message missing required "function" field');
        }

        $function = $data['function'];

        try {
            match ($function) {
                'BiospexBatchCreator' => $this->dispatchBatchCreatorJob($data),
                default => throw new InvalidArgumentException("Unknown function: {$function}"),
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
     * Dispatch a batch creator job based on message data.
     *
     * @param  array  $data  Message data containing batch processing results
     *
     * @throws InvalidArgumentException When required fields are missing
     * @throws RuntimeException When batch processing failed
     */
    private function dispatchBatchCreatorJob(array $data): void
    {
        $status = $data['status'] ?? throw new InvalidArgumentException('Missing status');
        $downloadId = $data['downloadId'] ?? throw new InvalidArgumentException('Missing downloadId');

        if (LambdaStatus::tryFrom($status) === LambdaStatus::Failed) {
            $error = $data['error'] ?? 'Unknown error';
            Log::error('BiospexBatchCreator failed', $data);
            throw new RuntimeException("Batch export failed for download #{$downloadId}: {$error}");
        }

        ZooniverseExportBatchResultJob::dispatch($data);
    }

    /**
     * Check if the batch queue has pending messages.
     *
     * @return bool True if messages > 0
     */
    private function hasActiveBatches(): bool
    {
        return $this->service->hasPendingMessages('batch_update');
    }
}
