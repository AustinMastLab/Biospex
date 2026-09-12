<?php

use Tests\TestCase;

uses(TestCase::class);

it('defaults the deployment update query operation to phase 8', function () {
    $contents = file_get_contents(base_path('deploy.php'));

    expect($contents)->toContain("set('update_queries_operation', 'wedigbio-phase-8');");
});

it('clears Composer cache and disables it during remote dependency installation', function () {
    $contents = file_get_contents(base_path('deploy/custom.php'));

    expect($contents)
        ->toContain('{{bin/composer}} clear-cache')
        ->toContain('install --prefer-dist --no-cache --no-progress --no-interaction');
});
