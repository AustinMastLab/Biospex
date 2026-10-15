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

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('rate limits API requests', function () {
    config(['config.api.rate_limit' => 2]);
    Sanctum::actingAs(User::factory()->create(), ['wedigbio-dashboard:read']);

    $this->getJson(route('api.v1.wedigbio-dashboard.index'));
    $this->getJson(route('api.v1.wedigbio-dashboard.index'));

    $this->getJson(route('api.v1.wedigbio-dashboard.index'))->assertTooManyRequests();
});

it('returns 403 for the WeDigBio dashboard when the token only has public:read', function () {
    Sanctum::actingAs(User::factory()->create(), ['public:read']);

    $this->getJson(route('api.v1.wedigbio-dashboard.index'))->assertForbidden();
});

it('checks the Reverb server certificate', function () {
    expect(config('broadcasting.connections.reverb.client_options.verify', true))->not->toBeFalse();
});
