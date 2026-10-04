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

use App\Services\Transcriptions\PusherTranscriptionService;
use MongoDB\BSON\UTCDateTime;

/**
 * Read the timestamp bounds out of the MongoDB filter the dashboard query would run.
 *
 * @param  array<string, string>  $request
 * @return array<string, string> Operator ($gte / $lte) => ISO-8601 UTC time.
 */
function dashboardTimestampBounds(array $request): array
{
    $filter = app(PusherTranscriptionService::class)->buildDashboardQuery($request)->toMql()['find'][0];
    $conditions = $filter['$and'] ?? [$filter];

    return collect($conditions)
        ->mapWithKeys(fn (array $condition) => $condition['timestamp'])
        ->map(fn (UTCDateTime $date) => $date->toDateTime()->format('Y-m-d\TH:i:s\Z'))
        ->sortKeys()
        ->all();
}

it('treats timestampStart as the older bound and timestampEnd as the newer bound', function () {
    expect(dashboardTimestampBounds([
        'timestampStart' => '2026-10-03T00:00:00Z',
        'timestampEnd' => '2026-10-04T00:00:00Z',
    ]))->toBe([
        '$gte' => '2026-10-03T00:00:00Z',
        '$lte' => '2026-10-04T00:00:00Z',
    ]);
});

it('uses now as the upper bound when only timestampStart is given', function () {
    $this->travelTo('2026-10-07 12:00:00');

    expect(dashboardTimestampBounds(['timestampStart' => '2026-10-07T11:00:00Z']))->toBe([
        '$gte' => '2026-10-07T11:00:00Z',
        '$lte' => '2026-10-07T12:00:00Z',
    ]);
});

it('has no lower bound when only timestampEnd is given', function () {
    expect(dashboardTimestampBounds(['timestampEnd' => '2026-10-04T00:00:00Z']))->toBe([
        '$lte' => '2026-10-04T00:00:00Z',
    ]);
});

it('returns everything up to now when no timestamps are given', function () {
    $this->travelTo('2026-10-07 12:00:00');

    expect(dashboardTimestampBounds([]))->toBe([
        '$lte' => '2026-10-07T12:00:00Z',
    ]);
});

it('reads timestamps with an offset as UTC instants', function () {
    expect(dashboardTimestampBounds([
        'timestampStart' => '2026-10-07T07:00:00-04:00',
        'timestampEnd' => '2026-10-07T08:00:00-04:00',
    ]))->toBe([
        '$gte' => '2026-10-07T11:00:00Z',
        '$lte' => '2026-10-07T12:00:00Z',
    ]);
});
