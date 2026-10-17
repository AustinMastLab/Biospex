<?php

use Illuminate\Support\Arr;

/**
 * Evaluate config/boost.php with the given environment variables in place, then restore them.
 *
 * @param  array<string, string|null>  $variables
 * @return array<string, mixed>
 */
function boostConfigWithEnvironment(array $variables): array
{
    $original = [];

    foreach ($variables as $name => $value) {
        $original[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];

        if ($value === null) {
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);
        } else {
            $_SERVER[$name] = $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }

    try {
        return require config_path('boost.php');
    } finally {
        foreach ($original as $name => [$server, $env, $putenv]) {
            $server === null ? Arr::forget($_SERVER, $name) : $_SERVER[$name] = $server;
            $env === null ? Arr::forget($_ENV, $name) : $_ENV[$name] = $env;
            $putenv === false ? putenv($name) : putenv("{$name}={$putenv}");
        }
    }
}

it('enables Boost only in the local environment by default', function (string $environment, bool $enabled) {
    $config = boostConfigWithEnvironment(['APP_ENV' => $environment, 'BOOST_ENABLED' => null]);

    expect($config['enabled'])->toBe($enabled);
})->with([
    'local' => ['local', true],
    'development' => ['development', false],
    'production' => ['production', false],
]);

it('lets BOOST_ENABLED override the default', function () {
    $config = boostConfigWithEnvironment(['APP_ENV' => 'development', 'BOOST_ENABLED' => 'true']);

    expect($config['enabled'])->toBeTrue();
});
