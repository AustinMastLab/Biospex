<?php

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('accepts phase 8 validation for the WeDigBio deployment flow', function () {
    DB::shouldReceive('selectOne')
        ->andReturnUsing(function (string $query, array $bindings = []) {
            if (str_contains($query, 'FROM information_schema.views') && $bindings === ['wedigbio_events']) {
                return (object) ['exists_flag' => 1];
            }

            if (str_contains($query, 'FROM information_schema.tables') && $bindings === ['wedigbio_events_legacy', 'BASE TABLE']) {
                return null;
            }

            if (str_contains($query, 'FROM information_schema.views') && $bindings === ['wedigbio_events_release_a_v', 'wedigbio_events_reports_v']) {
                return (object) ['cnt' => 0];
            }

            if ($query === 'SELECT COUNT(*) AS cnt FROM wedigbio_events') {
                return (object) ['cnt' => 12];
            }

            throw new RuntimeException('Unexpected query: '.$query);
        });

    DB::shouldReceive('statement')->never();

    $this->artisan('app:update-queries', ['operation' => 'wedigbio-phase-8'])
        ->assertExitCode(0);
});
