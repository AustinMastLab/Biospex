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

namespace App\Services\Transcriptions;

use App\Models\PusherTranscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service class for handling PusherTranscription operations including CRUD and dashboard queries.
 */
class PusherTranscriptionService
{
    /** @var Builder Query builder for dashboard operations */
    private Builder $dashboardQuery;

    /**
     * Create a new PusherTranscriptionService instance.
     */
    public function __construct(protected PusherTranscription $model) {}

    /**
     * Find a PusherTranscription by column and value.
     *
     * @param  string  $column  Column to search
     * @param  string  $value  Value to match
     */
    public function findBy(string $column, mixed $value): ?PusherTranscription
    {
        return $this->model->where($column, $value)->first();
    }

    /**
     * Create a new PusherTranscription record.
     */
    public function create(array $data): PusherTranscription
    {
        return $this->model->create($data);
    }

    /**
     * Update existing PusherTranscription record.
     */
    public function update(array $data, mixed $resourceId): PusherTranscription|false
    {
        $model = $this->model->find($resourceId);
        $result = $model->fill($data)->save();

        return $result ? $model : false;
    }

    /**
     * Get a total count of dashboard items.
     */
    public function getWeDigBioDashboardCount(): int
    {
        return $this->dashboardQuery->count();
    }

    /**
     * Get paginated dashboard items.
     */
    public function getWeDigBioDashboardItems(int $limit, int $offset): Collection
    {
        return $this->dashboardQuery->limit($limit)->offset($offset)->orderBy('timestamp', 'desc')->get();
    }

    /**
     * Set query builder for dashboard based on request parameters.
     */
    public function setQueryForDashboard(array $request): void
    {
        $this->dashboardQuery = $this->buildDashboardQuery($request);
    }

    /**
     * Build the dashboard query for a time window.
     *
     * timestampStart is the older (lower) bound and is optional; timestampEnd is
     * the newer (upper) bound and defaults to now. Both bounds are inclusive.
     *
     * @param  array{timestampStart?: string, timestampEnd?: string}  $request
     */
    public function buildDashboardQuery(array $request): Builder
    {
        $timestampStart = $this->setTimestampStart($request);
        $timestampEnd = $this->setTimestampEnd($request);

        return $this->model->newQuery()->where(function ($query) use ($timestampStart, $timestampEnd) {
            $query->where('timestamp', '<=', $timestampEnd);
            if ($timestampStart !== null) {
                $query->where('timestamp', '>=', $timestampStart);
            }
        });
    }

    /**
     * Get the start (lower) timestamp from the request, or null for no lower bound.
     */
    private function setTimestampStart(array $request): ?Carbon
    {
        return isset($request['timestampStart']) ? Carbon::parse($request['timestampStart'], 'UTC') : null;
    }

    /**
     * Get the end (upper) timestamp from the request, or the current time.
     */
    private function setTimestampEnd(array $request): Carbon
    {
        return isset($request['timestampEnd']) ? Carbon::parse($request['timestampEnd'], 'UTC') : Carbon::now('UTC');
    }
}
