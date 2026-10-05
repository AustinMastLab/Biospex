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

namespace App\Services\Api;

use Aws\Lambda\LambdaClient;
use Illuminate\Support\Facades\Log;

/**
 * Checks whether AWS Lambda functions are allowed to run.
 *
 * A function is paused by setting its reserved concurrency to 0 (see app:lambda-control).
 * The function names are the keys of config('services.aws.lambdas').
 */
class AwsLambdaApiService
{
    public function __construct(protected LambdaClient $lambdaClient) {}

    /**
     * Determine whether every given Lambda function can run.
     *
     * A function with no reserved concurrency limit can run. If the check itself fails
     * (for example an AWS API error or an unknown function name), a warning is logged and
     * the function is treated as able to run, so an AWS problem does not stop processing.
     *
     * @param  array<int, string>  $functionNames
     */
    public function canRun(array $functionNames): bool
    {
        foreach ($functionNames as $functionName) {
            if (! $this->functionCanRun($functionName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether a single Lambda function has a reserved concurrency above 0.
     */
    private function functionCanRun(string $functionName): bool
    {
        try {
            $result = $this->lambdaClient->getFunctionConcurrency([
                'FunctionName' => $functionName,
            ]);

            return ($result['ReservedConcurrentExecutions'] ?? 1) > 0;
        } catch (\Exception $e) {
            Log::warning("Could not check concurrency for Lambda {$functionName}: {$e->getMessage()}");

            return true;
        }
    }
}
