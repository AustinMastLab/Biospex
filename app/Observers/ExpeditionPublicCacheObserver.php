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

use App\Models\Expedition;
use Illuminate\Support\Facades\Cache;

/**
 * Observer for Expedition model that manages cache versioning for public expedition sorting.
 *
 * This observer increments a cache version number whenever expeditions are created, updated,
 * deleted, or restored to invalidate cached public expedition lists.
 */
class ExpeditionPublicCacheObserver
{
    /**
     * Increment the public expedition sort cache version.
     *
     * Creates the cache key with version 1 if it doesn't exist, otherwise increments
     * the existing version to invalidate cached expedition data.
     */
    private function bump(): void
    {
        if (! Cache::has('public_sort:expeditions:version')) {
            Cache::forever('public_sort:expeditions:version', 1);

            return;
        }

        Cache::increment('public_sort:expeditions:version');
    }

    /**
     * Handle the Expedition "created" event.
     *
     * @param  Expedition  $expedition  The expedition that was created
     */
    public function created(Expedition $expedition): void
    {
        $this->bump();
    }

    /**
     * Handle the Expedition "updated" event.
     *
     * @param  Expedition  $expedition  The expedition that was updated
     */
    public function updated(Expedition $expedition): void
    {
        $this->bump();
    }

    /**
     * Handle the Expedition "deleted" event.
     *
     * @param  Expedition  $expedition  The expedition that was deleted
     */
    public function deleted(Expedition $expedition): void
    {
        $this->bump();
    }

    /**
     * Handle the Expedition "restored" event.
     *
     * @param  Expedition  $expedition  The expedition that was restored
     */
    public function restored(Expedition $expedition): void
    {
        $this->bump();
    }
}
