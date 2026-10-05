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

namespace App\Console\Commands;

use Aws\Lambda\LambdaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Stops or starts a Lambda function, for example to halt a runaway function.
 *
 * Without --alias, the function's reserved concurrency is set to 0 (stop) or restored
 * from config('services.aws.lambdas') (start). Concurrency belongs to the function, so
 * this affects every environment.
 *
 * With --alias, only that alias's SQS triggers (event source mappings) are disabled or
 * enabled, leaving the other environments and the concurrency alone.
 */
class LambdaControlCommand extends Command
{
    /** @var array<int, string> Lambda aliases, one per environment. */
    private const ALIASES = ['prod', 'dev', 'loc'];

    protected $signature = 'app:lambda-control {lambda} {action=start}
        {--alias= : Only disable (stop) or enable (start) the SQS triggers of this alias: prod, dev or loc}';

    protected $description = 'Stop or start a Lambda: concurrency 0 in every environment, or one alias\'s SQS triggers with --alias';

    public function handle(LambdaClient $lambdaClient): int
    {
        $lambdaName = $this->argument('lambda');
        $action = strtolower($this->argument('action'));

        $lambdas = config('services.aws.lambdas', []);

        if (! isset($lambdas[$lambdaName])) {
            $this->error("Lambda '{$lambdaName}' not found in config");

            return self::FAILURE;
        }

        if (! in_array($action, ['start', 'stop'], true)) {
            throw new \InvalidArgumentException("Action must be 'start' or 'stop'");
        }

        $alias = $this->option('alias');

        if ($alias !== null) {
            return $this->setTriggers($lambdaClient, $lambdaName, $alias, $action === 'start');
        }

        return $this->setConcurrency($lambdaClient, $lambdaName, $action === 'stop' ? 0 : (int) $lambdas[$lambdaName]);
    }

    /**
     * Set the function's reserved concurrency, which applies to every alias.
     */
    private function setConcurrency(LambdaClient $lambdaClient, string $lambdaName, int $targetConcurrency): int
    {
        try {
            if ($targetConcurrency === 0) {
                $lambdaClient->putFunctionConcurrency([
                    'FunctionName' => $lambdaName,
                    'ReservedConcurrentExecutions' => 0,
                ]);
                Log::info("Stopped {$lambdaName} — concurrency set to 0");
            } else {
                // Remove any existing concurrency limit first
                try {
                    $lambdaClient->deleteFunctionConcurrency(['FunctionName' => $lambdaName]);
                } catch (\Exception $e) {
                    // Ignore if no concurrency limit exists
                }

                $lambdaClient->putFunctionConcurrency([
                    'FunctionName' => $lambdaName,
                    'ReservedConcurrentExecutions' => $targetConcurrency,
                ]);
                Log::info("Started {$lambdaName} — concurrency set to {$targetConcurrency}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Enable or disable every SQS trigger of one alias of the function.
     */
    private function setTriggers(LambdaClient $lambdaClient, string $lambdaName, string $alias, bool $enabled): int
    {
        if (! in_array($alias, self::ALIASES, true)) {
            $this->error('Alias must be one of: '.implode(', ', self::ALIASES));

            return self::FAILURE;
        }

        $functionName = "{$lambdaName}:{$alias}";

        try {
            $uuids = [];
            foreach ($lambdaClient->getPaginator('ListEventSourceMappings', ['FunctionName' => $functionName]) as $page) {
                foreach ($page['EventSourceMappings'] ?? [] as $mapping) {
                    $uuids[] = $mapping['UUID'];
                }
            }

            if ($uuids === []) {
                $this->error("{$functionName} has no SQS triggers. It may be started by S3 events instead; run the command without --alias to stop the function in every environment.");

                return self::FAILURE;
            }

            foreach ($uuids as $uuid) {
                $lambdaClient->updateEventSourceMapping(['UUID' => $uuid, 'Enabled' => $enabled]);
            }

            $state = $enabled ? 'Enabled' : 'Disabled';
            $message = "{$state} ".count($uuids)." SQS trigger(s) for {$functionName}";
            Log::info($message);
            $this->info($message);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
