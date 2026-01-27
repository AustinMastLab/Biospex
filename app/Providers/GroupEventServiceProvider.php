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

use App\Listeners\GroupEventSubscriber;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for registering group-related event subscribers.
 * TODO: After convertain all Reverb and Echo code to Livewire, can remove.
 *
 * This provider registers the GroupEventSubscriber to handle group-related
 * events such as user login, logout, and group modifications.
 */
class GroupEventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * Registers the GroupEventSubscriber to listen for group-related events.
     */
    public function boot(): void
    {
        Event::subscribe(GroupEventSubscriber::class);
    }
}
