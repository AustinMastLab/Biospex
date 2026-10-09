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

namespace App\Services\Cache;

use App\Models\WeDigBioEvent;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

/**
 * Caches pages like CacheAllSuccessfulGetRequests, but expires them when the
 * next active WeDigBio event starts or ends.
 *
 * The navigation menu and the WeDigBio event block depend on whether an event
 * is under way, and cached pages are not rendered again until they expire.
 */
class WeDigBioEventCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function cacheLifetimeInSeconds(Request $request): int
    {
        $lifetimeInSeconds = parent::cacheLifetimeInSeconds($request);
        $nextEventChange = $this->nextEventChange();

        if ($nextEventChange === null) {
            return $lifetimeInSeconds;
        }

        $secondsUntilChange = (int) ceil(now('UTC')->diffInSeconds($nextEventChange, true));

        return max(1, min($lifetimeInSeconds, $secondsUntilChange));
    }

    /**
     * The next start or end time of an active WeDigBio event, if any.
     */
    protected function nextEventChange(): ?CarbonInterface
    {
        $now = now('UTC');

        return WeDigBioEvent::active()
            ->where(fn ($query) => $query->where('start_date', '>', $now)->orWhere('end_date', '>', $now))
            ->get(['start_date', 'end_date'])
            ->flatMap(fn (WeDigBioEvent $event) => [$event->start_date, $event->end_date])
            ->filter(fn (CarbonInterface $date) => $date->greaterThan($now))
            ->min();
    }
}
