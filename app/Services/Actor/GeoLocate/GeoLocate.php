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

namespace App\Services\Actor\GeoLocate;

use App\Enums\ActorExpeditionState;
use App\Jobs\GeoLocateStatsJob;
use App\Models\ActorExpedition;

class GeoLocate
{
    /**
     * Process export job.
     *
     *  State = 0: GeoLocate workflow created.
     *  State = 1: Export file has been created.
     *  State = 2: GeoLocate community and datasource added.
     *  State = 3: GeoLocate with stats completed
     *
     * @throws \Throwable
     */
    public function process(ActorExpedition $actorExpedition): void
    {
        if ($actorExpedition->state === ActorExpeditionState::Processing && config('geolocate.enabled')) {
            GeoLocateStatsJob::dispatch($actorExpedition);
        }
    }
}
