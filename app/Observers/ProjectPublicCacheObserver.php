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

use App\Models\Project;
use Illuminate\Support\Facades\Cache;

class ProjectPublicCacheObserver
{
    private function bump(): void
    {
        if (! Cache::has('public_sort:projects:version')) {
            Cache::forever('public_sort:projects:version', 1);

            return;
        }

        Cache::increment('public_sort:projects:version');
    }

    public function created(Project $project): void
    {
        $this->bump();
    }

    public function updated(Project $project): void
    {
        $this->bump();
    }

    public function deleted(Project $project): void
    {
        $this->bump();
    }

    public function restored(Project $project): void
    {
        $this->bump();
    }
}
