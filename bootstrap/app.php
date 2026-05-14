<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\FlashHelperMessage;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Honeypot\ProtectAgainstSpam;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;

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
            ProtectAgainstSpam::class,
        ]);

        // Global middleware stack (runs on every request)
        $middleware->use([
            InvokeDeferredCallbacks::class,
            TrustProxies::class,
            HandleCors::class,
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ]);

        // Web middleware group (applied to routes/web.php)
        $middleware->web(
            append: [
                FlashHelperMessage::class,
                CacheResponse::class,
            ], replace: [
                EncryptCookies::class => App\Http\Middleware\EncryptCookies::class,
            ]
        );

        // API middleware group (applied to routes/api.php)
        $middleware->api(
            append: [
                EnsureFrontendRequestsAreStateful::class,
            ], remove: ['throttle:api']
        );

        // Middleware aliases (for use in route definitions)
        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'ability' => CheckForAnyAbility::class,
            'doNotCacheResponse' => DoNotCacheResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
