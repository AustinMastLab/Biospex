<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
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

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for extending framework support classes.
 *
 * This provider registers custom macros and extensions for Laravel's
 * support classes to provide additional functionality throughout the application.
 */
class SupportServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Registers custom Collection macros for use throughout the application.
     */
    public function register(): void
    {
        /**
         * Shuffle collection and return key-value pairs.
         *
         * Shuffles the collection keys and returns a new collection
         * containing arrays of [key, value] pairs in the shuffled order.
         */
        Collection::macro('shuffleWords', function () {
            $keys = $this->keys()->shuffle();

            return $keys->map(function ($key) {
                return [$key, $this[$key]];
            });
        });
    }
}
