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

namespace App\Traits;

use App\Models\Expedition;

/**
 * Checks the expedition's skip flags, set in the admin panel.
 */
trait SkipZooniverse
{
    /**
     * Whether the expedition is excluded from reconciliation and expert review.
     */
    protected function skipReconcile(int|string $expeditionId): bool
    {
        return Expedition::whereKey($expeditionId)->where('skip_reconcile', true)->exists();
    }

    /**
     * Whether the expedition is excluded from Panoptes API calls.
     */
    protected function skipApi(int|string $expeditionId): bool
    {
        return Expedition::whereKey($expeditionId)->where('skip_api', true)->exists();
    }
}
