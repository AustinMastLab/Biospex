<?php

use Tests\TestCase;

uses(TestCase::class);

it('never serves a bingo card from the response cache, so each visitor is their own player', function () {
    $route = app('router')->getRoutes()->getByName('front.bingos.join');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('doNotCacheResponse');
});
