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

namespace App\Observers;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;

/**
 * Observer for Event model that manages public events cache versioning.
 *
 * This observer increments a cache version number whenever Event models are
 * created, updated, deleted, or restored. The version is used to invalidate
 * cached public event listings in the front-end event sort controller.
 */
class EventPublicCacheObserver
{
    /**
     * Increment the public events cache version number.
     *
     * Creates the cache key with initial value of 1 if it doesn't exist,
     * otherwise increments the existing version number. This invalidates
     * any cached public event listings that depend on this version.
     */
    private function bump(): void
    {
        if (! Cache::has('public_sort:events:version')) {
            Cache::forever('public_sort:events:version', 1);

            return;
        }

        Cache::increment('public_sort:events:version');
    }

    /**
     * Handle the Event "created" event.
     *
     * @param  Event  $event  The event instance that was created
     */
    public function created(Event $event): void
    {
        $this->bump();
    }

    /**
     * Handle the Event "updated" event.
     *
     * @param  Event  $event  The event instance that was updated
     */
    public function updated(Event $event): void
    {
        $this->bump();
    }

    /**
     * Handle the Event "deleted" event.
     *
     * @param  Event  $event  The event instance that was deleted
     */
    public function deleted(Event $event): void
    {
        $this->bump();
    }

    /**
     * Handle the Event "restored" event.
     *
     * @param  Event  $event  The event instance that was restored
     */
    public function restored(Event $event): void
    {
        $this->bump();
    }
}
