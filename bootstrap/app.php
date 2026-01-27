<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $require_files = function ($dir) {
                $files = File::files(base_path($dir));
                foreach ($files as $file) {
                    require $file->getPathname();
                }
            };

            // Migrated from mapWebRoutes()
            Route::domain(config('app.domain'))->middleware('web')->group(function () use ($require_files) {
                $require_files('routes/front');
                $require_files('routes/front/appauth');

                Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () use ($require_files) {
                    $require_files('routes/admin');
                    Route::get('/', function () {
                        return Redirect::route('admin.projects.index');
                    });
                });
            });

            // Migrated from mapApiRoutes()
            Route::domain(config('config.api.domain'))->group(function () use ($require_files) {
                Route::middleware(['web'])->group(base_path('routes/api/index.php'));

                Route::prefix('v1')->middleware([
                    'api',
                    'auth:sanctum',
                    'ability:panoptes-pusher:read,panoptes-pusher:create,wedigbio-dashboard:read,lambda:update',
                ])->group(function () use ($require_files) {
                    $require_files('routes/api/v1');
                });
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \Spatie\Honeypot\ProtectAgainstSpam::class,
        ]);

        // Global middleware stack (runs on every request)
        $middleware->use([
            \Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks::class,
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // Web middleware group (applied to routes/web.php)
        $middleware->web(
            append: [
                \App\Http\Middleware\FlashHelperMessage::class,
                \Spatie\ResponseCache\Middlewares\CacheResponse::class,
            ], replace: [
                \Illuminate\Cookie\Middleware\EncryptCookies::class => \App\Http\Middleware\EncryptCookies::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => \App\Http\Middleware\VerifyCsrfToken::class,
            ]
        );

        // API middleware group (applied to routes/api.php)
        $middleware->api(
            append: [
                \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            ], remove: ['throttle:api']
        );

        // Middleware aliases (for use in route definitions)
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'doNotCacheResponse' => \Spatie\ResponseCache\Middlewares\DoNotCacheResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
