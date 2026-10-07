<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->withoutVite();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Read the wire:key of the rendered infinite-scroll trigger, or null when none is rendered.
 */
function loadMoreTriggerKey(Testable $component): ?string
{
    preg_match('/wire:key="([a-z-]*load-more-[^"]+)"/', $component->html(), $matches);

    return $matches[1] ?? null;
}

/**
 * Call loadMore the way the browser does: through the `cards` island in append mode.
 *
 * Returns only the HTML appended for the new page; earlier pages stay in the
 * browser and are not sent again.
 */
function loadMoreCards(Testable $component): string
{
    $component->update(calls: [[
        'method' => 'loadMore',
        'params' => [],
        'path' => '',
        'metadata' => ['island' => ['name' => 'cards', 'mode' => 'append']],
    ]]);

    return implode('', $component->effects['islandFragments'] ?? []);
}
