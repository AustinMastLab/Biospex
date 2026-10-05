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

use App\Services\Api\AwsLambdaApiService;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\Lambda\LambdaClient;
use Aws\MockHandler;
use Aws\Result;
use Illuminate\Support\Facades\Log;

/**
 * Build the service on a Lambda client whose responses come from the given handler.
 */
function lambdaApiServiceWith(MockHandler $handler): AwsLambdaApiService
{
    return new AwsLambdaApiService(new LambdaClient([
        'region' => 'us-east-2',
        'version' => 'latest',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'handler' => $handler,
    ]));
}

it('allows a function that has no reserved concurrency limit', function () {
    $service = lambdaApiServiceWith(new MockHandler([new Result([])]));

    expect($service->canRun(['BiospexImageFetcher']))->toBeTrue();
});

it('blocks when any of the functions is paused', function () {
    $checked = [];
    $concurrencyFor = function (int $concurrency) use (&$checked) {
        return function (CommandInterface $command) use (&$checked, $concurrency) {
            $checked[] = $command['FunctionName'];

            return new Result(['ReservedConcurrentExecutions' => $concurrency]);
        };
    };
    $service = lambdaApiServiceWith(new MockHandler([$concurrencyFor(100), $concurrencyFor(0)]));

    $canRun = $service->canRun(['BiospexImageFetcher', 'BiospexOcrProcessor']);

    expect($canRun)->toBeFalse()
        ->and($checked)->toBe(['BiospexImageFetcher', 'BiospexOcrProcessor']);
});

it('allows the function and logs a warning when its concurrency cannot be read', function () {
    $service = lambdaApiServiceWith(new MockHandler([
        fn (CommandInterface $command) => new AwsException('Function not found', $command),
    ]));
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, 'BiospexImageFetcher'));

    expect($service->canRun(['BiospexImageFetcher']))->toBeTrue();
});
