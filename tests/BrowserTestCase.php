<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base case for browser tests (pestphp/pest-plugin-browser).
 *
 * Unlike TestCase it keeps Vite, so pages load the built assets (run `yarn build` first). The web routes are bound to
 * config('app.domain'), so the app is booted for 127.0.0.1, the address the test server listens on: the routes
 * match, and no link or redirect can lead to a real site. Sessions use files, because a real
 * browser makes several requests.
 */
abstract class BrowserTestCase extends BaseTestCase
{
    public const HOST = '127.0.0.1';

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $overrides = [
            'APP_DOMAIN' => self::HOST,
            'APP_URL' => 'http://'.self::HOST,
            'SESSION_DOMAIN' => '',
            'SESSION_DRIVER' => 'file',
            'SESSION_SECURE_COOKIE' => 'false',
        ];
        $original = [];

        foreach ($overrides as $name => $value) {
            $original[$name] = getenv($name);
            self::setEnvironmentValue($name, $value);
        }

        try {
            $app = require __DIR__.'/../bootstrap/app.php';

            $app->make(Kernel::class)->bootstrap();
        } finally {
            // The booted app keeps the values; restore them so later tests in this process boot normally.
            foreach ($original as $name => $value) {
                self::setEnvironmentValue($name, $value);
            }
        }

        return $app;
    }

    /**
     * Set or (with false) remove an environment variable everywhere Laravel reads it.
     */
    private static function setEnvironmentValue(string $name, string|false $value): void
    {
        if ($value === false) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);

            return;
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
