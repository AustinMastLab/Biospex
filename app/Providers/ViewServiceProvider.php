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

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for configuring view composers and Blade extensions.
 *
 * This provider registers view composers for common application views
 * and extends Blade with custom directives for enhanced templating functionality.
 */
class ViewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap application view services.
     *
     * Registers view composers for common views and sets up custom Blade directives.
     * View composers automatically inject data into specified views when they are rendered.
     */
    public function boot(): void
    {
        $this->setupBlade();

        View::composer('common.notices', 'App\Http\ViewComposers\NoticesComposer');
        View::composer(['common.process-modal', 'common.modal'], 'App\Http\ViewComposers\PhpVarsComposer');
        View::composer('common.nav', 'App\Http\ViewComposers\NavComposer');
    }

    /**
     * Configure custom Blade compiler extensions.
     *
     * Extends Blade to support @break and @continue directives for use within loops,
     * converting them to their PHP equivalents during template compilation.
     */
    protected function setupBlade(): void
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->extend(function ($value) {
            return preg_replace('/(\s*)@(break|continue)(\s*)/', '$1<?php $2; ?>$3', $value);
        });
    }
}
