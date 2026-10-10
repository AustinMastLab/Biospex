<?php

use Tests\TestCase;

uses(TestCase::class);

it('keeps the expedition size within the ZipBatchOrchestrator file ranges', function () {
    expect((int) config('config.expedition_size'))->toBeLessThanOrEqual(
        20000,
        'The ZipBatchOrchestrator state machine zips files 0-19,999 in four fixed ranges. '.
        'Add ranges to its SplitFiles state before raising config.expedition_size.'
    );
});
