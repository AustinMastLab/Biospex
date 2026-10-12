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

use App\Jobs\TesseractOcrUpdateJob;
use App\Services\SqsListenerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class SqsListenerOcrUpdate extends Command
{
    protected $signature = 'ocr:listen';

    protected $description = 'Robust SQS listener for OCR update queue with reconnections and alerts';

    public function __construct(protected SqsListenerService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->validateConfiguration();
            $this->runWorker();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->service->handleCriticalError('Failed to start OCR updates listener', $e, $this);

            return self::FAILURE;
        }
    }

    private function validateConfiguration(): void
    {
        $required = [
            'services.aws.sqs.ocr_update',
            'services.aws.region',
        ];

        foreach ($required as $key) {
            if (empty(Config::get($key))) {
                throw new RuntimeException("Missing configuration: {$key}");
            }
        }
    }

    private function runWorker(): void
    {
        $idleChecker = fn () => $this->hasActiveOcrJobs();
        $queueKey = 'ocr_update';
        $graceKey = 'services.aws.ocr_idle_grace';
        $routeCallback = fn ($body) => $this->routeMessage($body);

        $this->service->run($idleChecker, $queueKey, $graceKey, $routeCallback, $this);
    }

    public function routeMessage(array $data): void
    {
        // Allow either fileId OR subjectId for flexibility (especially for DLQ or metadata-only updates)
        if (! isset($data['fileId']) && ! isset($data['subjectId'])) {
            throw new InvalidArgumentException('Invalid message format: Missing both fileId and subjectId');
        }

        $status = $data['status'] ?? throw new InvalidArgumentException('Missing status');

        // Dispatch job for BOTH success and failure
        TesseractOcrUpdateJob::dispatch($data);
    }

    private function hasActiveOcrJobs(): bool
    {
        return $this->service->hasPendingMessages('ocr_update');
    }
}
