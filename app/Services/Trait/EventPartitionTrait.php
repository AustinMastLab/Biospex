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

namespace App\Services\Trait;

use Illuminate\Support\Collection;

trait EventPartitionTrait
{
    /**
     * Partition events into incomplete and complete.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    public function partitionEvents(Collection $records): array
    {
        $partitions = $records
            ->partition(function ($event) {
                return $this->dateService->eventBefore($event) || $this->dateService->eventActive($event);
            })
            ->values(); // ensure numeric keys 0,1

        return [$partitions->get(0, collect()), $partitions->get(1, collect())];
    }
}
