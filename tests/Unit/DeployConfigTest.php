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

it('restarts long-running processes after the release is published', function (string $restartTask) {
    $contents = file_get_contents(base_path('deploy.php'));

    preg_match("/task\('deploy', \[(.*?)\]\);/s", $contents, $matches);
    preg_match_all("/'([a-z:_-]+)',/", $matches[1], $tasks);
    $order = array_flip($tasks[1]);

    expect($order)->toHaveKeys(['deploy:publish', $restartTask])
        ->and($order[$restartTask])->toBeGreaterThan($order['deploy:publish']);
})->with([
    'Supervisor config reload' => 'supervisor:reload',
    'queue workers' => 'artisan:queue:restart',
    'Reverb' => 'artisan:reverb:restart',
    'Panoptes listener' => 'supervisor:restart-panoptes-listener',
]);

it('lets Supervisor bring Reverb back after it stops', function () {
    $config = parse_ini_file(resource_path('supervisor/reverb.conf'), true, INI_SCANNER_RAW);
    $program = reset($config);

    expect($program['autorestart'])->toBe('true');
});

it('clears Composer cache and disables it during remote dependency installation', function () {
    $contents = file_get_contents(base_path('deploy/custom.php'));

    expect($contents)
        ->toContain('{{bin/composer}} clear-cache')
        ->toContain('install --prefer-dist --no-cache --no-progress --no-interaction');
});
