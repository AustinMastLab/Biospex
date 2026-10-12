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

use App\Models\WeDigBioEvent;
use App\Services\Cache\WeDigBioEventCacheProfile;
use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;

beforeEach(function () {
    config(['responsecache.cache.lifetime_in_seconds' => 60 * 60 * 24 * 7]);
    $this->travelTo('2026-10-10 12:00:00 UTC');
});

afterEach(function () {
    $this->travelBack();
});

function eventCacheLifetime(): int
{
    return app(WeDigBioEventCacheProfile::class)->cacheLifetimeInSeconds(Request::create('/'));
}

it('is the cache profile the response cache uses', function () {
    expect(app(CacheProfile::class))->toBeInstanceOf(WeDigBioEventCacheProfile::class);
});

it('uses the configured lifetime when no event is coming up', function () {
    expect(eventCacheLifetime())->toBe(60 * 60 * 24 * 7);
});

it('expires pages when the next event starts', function () {
    WeDigBioEvent::factory()->create([
        'active' => true,
        'start_date' => '2026-10-10 13:00:00',
        'end_date' => '2026-10-11 13:00:00',
    ]);

    expect(eventCacheLifetime())->toBe(60 * 60);
});

it('expires pages when the event under way ends', function () {
    WeDigBioEvent::factory()->create([
        'active' => true,
        'start_date' => '2026-10-09 12:00:00',
        'end_date' => '2026-10-10 14:30:00',
    ]);

    expect(eventCacheLifetime())->toBe(150 * 60);
});

it('ignores events that are not active', function () {
    WeDigBioEvent::factory()->create([
        'active' => false,
        'start_date' => '2026-10-10 13:00:00',
        'end_date' => '2026-10-11 13:00:00',
    ]);

    expect(eventCacheLifetime())->toBe(60 * 60 * 24 * 7);
});

it('keeps the configured lifetime when the next event is further away', function () {
    WeDigBioEvent::factory()->create([
        'active' => true,
        'start_date' => '2027-04-10 12:00:00',
        'end_date' => '2027-04-11 12:00:00',
    ]);

    expect(eventCacheLifetime())->toBe(60 * 60 * 24 * 7);
});
