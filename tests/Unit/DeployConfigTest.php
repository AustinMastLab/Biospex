<?php

use App\Console\Commands\AppUpdateQueriesCommand;
use Tests\TestCase;

uses(TestCase::class);

it('runs update queries right after migrations during deployment', function () {
    $contents = file_get_contents(base_path('deploy.php'));

    expect($contents)->toMatch("/'artisan:migrate',[^\n]*\n\s*'artisan:app:update-queries',/");
});

it('configures an update query operation the command supports', function () {
    $contents = file_get_contents(base_path('deploy.php'));

    expect(preg_match("/set\('update_queries_operation', '([^']*)'\);/", $contents, $matches))->toBe(1);

    $operation = $matches[1];

    if ($operation !== '') {
        $signature = (new ReflectionProperty(AppUpdateQueriesCommand::class, 'signature'))
            ->getDefaultValue();

        expect($signature)->toContain($operation);
    }
});

it('clears Composer cache and disables it during remote dependency installation', function () {
    $contents = file_get_contents(base_path('deploy/custom.php'));

    expect($contents)
        ->toContain('{{bin/composer}} clear-cache')
        ->toContain('install --prefer-dist --no-cache --no-progress --no-interaction');
});
