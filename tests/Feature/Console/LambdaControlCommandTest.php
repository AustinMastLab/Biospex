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

use Aws\CommandInterface;
use Aws\Lambda\LambdaClient;
use Aws\MockHandler;
use Aws\Result;

/**
 * Bind a Lambda client that answers with the given results in order and records
 * each AWS call as [operation, arguments] in $calls.
 *
 * @param  array<int, array<string, mixed>>  $results
 * @param  array<int, array{0: string, 1: array<string, mixed>}>  $calls
 */
function fakeLambdaClient(array $results, array &$calls): void
{
    $handler = new MockHandler;
    foreach ($results as $result) {
        $handler->append(function (CommandInterface $command) use ($result, &$calls) {
            $arguments = array_filter($command->toArray(), fn ($key) => $key[0] !== '@', ARRAY_FILTER_USE_KEY);
            $calls[] = [$command->getName(), $arguments];

            return new Result($result);
        });
    }

    app()->instance(LambdaClient::class, new LambdaClient([
        'region' => 'us-east-2',
        'version' => 'latest',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'handler' => $handler,
    ]));
}

it('disables only the given alias\'s SQS triggers when stopping with --alias', function () {
    $calls = [];
    fakeLambdaClient([
        ['EventSourceMappings' => [['UUID' => '11111111-1111-1111-1111-111111111111'], ['UUID' => '22222222-2222-2222-2222-222222222222']]],
        ['State' => 'Disabling'],
        ['State' => 'Disabling'],
    ], $calls);

    $this->artisan('app:lambda-control', ['lambda' => 'BiospexImageFetcher', 'action' => 'stop', '--alias' => 'dev'])
        ->expectsOutput('Disabled 2 SQS trigger(s) for BiospexImageFetcher:dev')
        ->assertSuccessful();

    expect($calls)->toBe([
        ['ListEventSourceMappings', ['FunctionName' => 'BiospexImageFetcher:dev']],
        ['UpdateEventSourceMapping', ['UUID' => '11111111-1111-1111-1111-111111111111', 'Enabled' => false]],
        ['UpdateEventSourceMapping', ['UUID' => '22222222-2222-2222-2222-222222222222', 'Enabled' => false]],
    ]);
});

it('enables the given alias\'s SQS triggers when starting with --alias', function () {
    $calls = [];
    fakeLambdaClient([
        ['EventSourceMappings' => [['UUID' => '11111111-1111-1111-1111-111111111111']]],
        ['State' => 'Enabling'],
    ], $calls);

    $this->artisan('app:lambda-control', ['lambda' => 'BiospexImageFetcher', 'action' => 'start', '--alias' => 'loc'])
        ->assertSuccessful();

    expect($calls)->toBe([
        ['ListEventSourceMappings', ['FunctionName' => 'BiospexImageFetcher:loc']],
        ['UpdateEventSourceMapping', ['UUID' => '11111111-1111-1111-1111-111111111111', 'Enabled' => true]],
    ]);
});

it('fails without changing anything when the alias has no SQS triggers', function () {
    $calls = [];
    fakeLambdaClient([['EventSourceMappings' => []]], $calls);

    $this->artisan('app:lambda-control', ['lambda' => 'BiospexOcrProcessor', 'action' => 'stop', '--alias' => 'dev'])
        ->expectsOutputToContain('BiospexOcrProcessor:dev has no SQS triggers')
        ->assertFailed();

    expect($calls)->toBe([
        ['ListEventSourceMappings', ['FunctionName' => 'BiospexOcrProcessor:dev']],
    ]);
});

it('rejects an unknown alias without calling AWS', function () {
    $calls = [];
    fakeLambdaClient([], $calls);

    $this->artisan('app:lambda-control', ['lambda' => 'BiospexImageFetcher', 'action' => 'stop', '--alias' => 'staging'])
        ->expectsOutput('Alias must be one of: prod, dev, loc')
        ->assertFailed();

    expect($calls)->toBe([]);
});

it('sets the function\'s concurrency to 0 when stopping without --alias', function () {
    $calls = [];
    fakeLambdaClient([['ReservedConcurrentExecutions' => 0]], $calls);

    $this->artisan('app:lambda-control', ['lambda' => 'BiospexReconcile312', 'action' => 'stop'])
        ->assertSuccessful();

    expect($calls)->toBe([
        ['PutFunctionConcurrency', ['FunctionName' => 'BiospexReconcile312', 'ReservedConcurrentExecutions' => 0]],
    ]);
});

it('restores the configured concurrency when starting without --alias', function () {
    $calls = [];
    fakeLambdaClient([[], ['ReservedConcurrentExecutions' => 100]], $calls);

    $this->artisan('app:lambda-control', ['lambda' => 'BiospexImageFetcher', 'action' => 'start'])
        ->assertSuccessful();

    expect($calls)->toBe([
        ['DeleteFunctionConcurrency', ['FunctionName' => 'BiospexImageFetcher']],
        ['PutFunctionConcurrency', ['FunctionName' => 'BiospexImageFetcher', 'ReservedConcurrentExecutions' => 100]],
    ]);
});
