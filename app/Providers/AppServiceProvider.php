<?php

/*
 * Copyright (C) 2014 - 2025, Biospex
 * biospex@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Providers;

use App\Models\Event;
use App\Models\Expedition;
use App\Observers\EventPublicCacheObserver;
use App\Observers\ExpeditionPublicCacheObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use Schema;

/**
 * Application service provider.
 *
 * Configures global application settings, database defaults, Redis events,
 * pagination rendering, and Eloquent model behaviors for development environments.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * Configures database schema defaults, enables Redis events, sets Bootstrap
     * pagination style, and enables strict Eloquent behaviors in non-production
     * environments to prevent lazy loading and missing attribute access.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Redis::enableEvents();
        Paginator::useBootstrap();

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());

        // Register model observers
        Event::observe(EventPublicCacheObserver::class);
        Expedition::observe(ExpeditionPublicCacheObserver::class);
    }

    /**
     * Register any application services.
     *
     * Registers IDE helper service provider in non-production environments
     * to provide enhanced IDE support and autocompletion.
     */
    public function register(): void
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }
}
