<?php

use Tests\TestCase;

uses(TestCase::class);

it('disables response caching for wedigbio front routes', function (string $routeName) {
    $route = app('router')->getRoutes()->getByName($routeName);
    $middleware = $route?->gatherMiddleware() ?? [];

    expect($route)->not->toBeNull()
        ->and($middleware)->toContain('doNotCacheResponse');
})->with([
    'front.wedigbio.index',
    'front.wedigbio-progress',
    'front.get.wedigbio-rate',
    'front.get.wedigbio-projects',
]);
