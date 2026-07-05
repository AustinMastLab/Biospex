<?php

use Tests\TestCase;

uses(TestCase::class);

it('defaults the deployment update query operation to phase 8', function () {
    $contents = file_get_contents(base_path('deploy.php'));

    expect($contents)->toContain("set('update_queries_operation', 'wedigbio-phase-8');");
});
