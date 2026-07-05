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

namespace App\Services\WeDigBio;

use App\Models\WeDigBioEvent;
use App\Services\Trait\RateChartTrait;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class WeDigBioRateService
 */
class WeDigBioRateService
{
    use RateChartTrait;

    /**
     * AjaxService constructor.
     */
    public function __construct(
        protected WeDigBioService $weDigBioService,
        protected Carbon $carbon
    ) {}

    /**
     * Get wedigbio event transcription data for step chart.
     */
    public function getWeDigBioEventRateChart(WeDigBioEvent $event, ?string $timestamp = null): ?array
    {
        $weDigBioDate = $this->weDigBioService->getActiveOrComplete($event);

        if ($weDigBioDate === null) {
            return null;
        }

        $loadTime = $this->getLoadTime($weDigBioDate, $this->carbon, $timestamp);

        $startLoad = $loadTime->copy();

        $endLoad = $this->getEndLoad($weDigBioDate, $loadTime, $timestamp);

        $intervals = $this->setTimeIntervals($startLoad, $endLoad, $timestamp);

        $transcriptions = $this->weDigBioService->getWeDigBioRateChartTranscriptions($weDigBioDate->id, $startLoad,
            $endLoad);

        $projects = $transcriptions->map(function ($transcription) {
            return $transcription->project->title;
        })->unique();

        return $transcriptions->isEmpty() ? $this->processEmptyResult($weDigBioDate->end_date, $projects,
            $intervals) : $this->processTranscriptionResult($projects, $transcriptions, $intervals);
    }

    /**
     * Process an empty result set as this means it's very beginning.
     */
    public function processEmptyResult(Carbon $end_date, Collection $projects, Collection $intervals): array
    {
        if ($this->carbon::now('UTC')->gt($end_date)) {
            return [];
        }

        $data = [];
        $intervals->each(function ($interval, $key) use (&$data, $projects) {
            $projects->each(function ($project) use (&$data, $key) {
                $data[$key][$project] = 0;
            });
        });

        return $data;
    }

    /**
     * Process query data for results.
     */
    protected function processTranscriptionResult(
        Collection $projects,
        Collection $transcriptions,
        Collection $intervals
    ): array {
        $mapped = $this->mapWithDateKeys($transcriptions);

        $merged = $this->mergeIntervals($mapped, $intervals);

        $transformed = $this->addMissingProjectCount($projects, $merged);

        return $this->setDateInArray($transformed);
    }

    /**
     * Map projects and data using keys.
     * Count is per hour (count * 12) for each 5 minutes.
     */
    protected function mapWithDateKeys(Collection $transcriptions): Collection
    {
        $data = [];
        $transcriptions->each(function ($transcription) use (&$data) {
            $date = $this->carbon::parse($transcription->time)->timezone('UTC')->format('Y-m-d H:i:s');
            $data[$date][$transcription->project->title] = $transcription->count * 12;
        });

        return collect($data);
    }

    /**
     * Merge mapped and intervals.
     */
    protected function mergeIntervals(Collection $mapped, Collection $intervals): Collection
    {
        return $intervals->merge($mapped)->map(function ($collection) {
            return is_array($collection) ? $collection : [];
        });
    }

    /**
     * Add missing team counts. Set to 0.
     */
    protected function addMissingProjectCount(Collection $projects, Collection $merged): Collection
    {
        return $merged->transform(function ($collection, $key) use ($projects) {
            $projects->each(function ($project) use (&$collection) {
                if (! isset($collection[$project])) {
                    $collection[$project] = 0;
                }
            });

            return $collection;
        });
    }

    /**
     * Get end load time. If wedigbio event is over, it will display all points from beginning to end.
     */
    protected function getEndLoad(
        WeDigBioEvent $weDigBioEvent,
        Carbon $loadTime,
        ?string $timestamp = null
    ): Carbon {
        $now = $this->carbon::now('UTC');
        if ($now->gt($weDigBioEvent->end_date)) {
            return $weDigBioEvent->end_date;
        }

        return $timestamp === null ? $now->floorMinutes(5) : $loadTime->addMinutes(5);
    }
}
