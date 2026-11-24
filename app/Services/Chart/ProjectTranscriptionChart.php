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

namespace App\Services\Chart;

use App\Models\Expedition;
use App\Models\PanoptesTranscription;
use App\Models\Project;
use App\Models\ProjectDailyTranscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;

/**
 * A project's Digitizations chart data, independent of any chart library: transcriptions per expedition per day in
 * project_daily_transcriptions, and a year's running totals in a neutral shape any chart can draw.
 */
class ProjectTranscriptionChart
{
    public function __construct(protected PanoptesTranscription $transcription) {}

    /**
     * Count the project's transcriptions per expedition per day and store them.
     *
     * By default only the last stored day onward is counted again (new transcriptions arrive daily); $full counts
     * every day, for a first build or after transcriptions were removed.
     *
     * @return int The number of stored days that were written.
     */
    public function refresh(Project $project, bool $full = false): int
    {
        $workflows = $this->expeditionsByWorkflow($project);

        if ($workflows->isEmpty()) {
            ProjectDailyTranscription::where('project_id', $project->id)->delete();

            return 0;
        }

        $since = $full ? null : $this->lastStoredDate($project);
        $rows = $this->countPerDay($workflows, $since)->map(fn (array $row) => [
            'project_id' => $project->id,
            'expedition_id' => $workflows[$row['workflow_id']],
            'date' => $row['date'],
            'count' => $row['count'],
        ]);

        DB::transaction(function () use ($project, $since, $rows) {
            ProjectDailyTranscription::where('project_id', $project->id)
                ->when($since !== null, fn ($query) => $query->where('date', '>=', $since))
                ->delete();

            $rows->chunk(1000)->each(fn (Collection $chunk) => ProjectDailyTranscription::insert($chunk->values()->all()));
        });

        return $rows->count();
    }

    /**
     * The years the project has transcriptions in, newest first.
     *
     * @return array<int, int>
     */
    public function years(Project $project): array
    {
        return ProjectDailyTranscription::where('project_id', $project->id)
            ->distinct()
            ->pluck('date')
            ->map(fn ($date) => (int) Carbon::parse($date)->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * A year of the chart: every day of the year (to today in the current year) and, for each expedition with
     * transcriptions that year, its running total on each day.
     *
     * @return array{year: int, dates: array<int, string>, series: array<int, array{expeditionId: int, name: string, total: int, values: array<int, int>}>}
     */
    public function year(Project $project, int $year): array
    {
        $start = Carbon::create($year, 1, 1);
        $end = $year === (int) now()->year ? now()->startOfDay() : Carbon::create($year, 12, 31);
        $dates = collect(range(0, (int) $start->diffInDays($end)))->map(fn (int $day) => $start->copy()->addDays($day)->toDateString());

        $counts = ProjectDailyTranscription::where('project_id', $project->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['expedition_id', 'date', 'count'])
            ->groupBy('expedition_id');

        $titles = Expedition::whereIn('id', $counts->keys())->pluck('title', 'id');

        $series = $counts->map(function (Collection $days, int $expeditionId) use ($dates, $titles) {
            $byDate = $days->mapWithKeys(fn (ProjectDailyTranscription $day) => [$day->date->toDateString() => $day->count]);
            $total = 0;

            return [
                'expeditionId' => $expeditionId,
                'name' => (string) ($titles[$expeditionId] ?? 'Expedition '.$expeditionId),
                'total' => (int) $byDate->sum(),
                'values' => $dates->map(function (string $date) use ($byDate, &$total) {
                    return $total += (int) ($byDate[$date] ?? 0);
                })->all(),
            ];
        })->sortBy('expeditionId')->values()->all();

        return ['year' => $year, 'dates' => $dates->all(), 'series' => $series];
    }

    /**
     * The project's expeditions on Zooniverse, keyed by workflow id; transcriptions record their workflow.
     *
     * @return Collection<int, int>
     */
    protected function expeditionsByWorkflow(Project $project): Collection
    {
        return Expedition::where('project_id', $project->id)
            ->with('panoptesProject:id,expedition_id,panoptes_workflow_id')
            ->get(['id'])
            ->filter(fn (Expedition $expedition) => $expedition->panoptesProject?->panoptes_workflow_id !== null)
            ->mapWithKeys(fn (Expedition $expedition) => [(int) $expedition->panoptesProject->panoptes_workflow_id => $expedition->id]);
    }

    protected function lastStoredDate(Project $project): ?string
    {
        $date = ProjectDailyTranscription::where('project_id', $project->id)->max('date');

        return $date === null ? null : Carbon::parse($date)->toDateString();
    }

    /**
     * One aggregation for all of the project's workflows: transcriptions per workflow per day (UTC).
     *
     * @param  Collection<int, int>  $workflows
     * @return Collection<int, array{workflow_id: int, date: string, count: int}>
     */
    protected function countPerDay(Collection $workflows, ?string $since): Collection
    {
        $match = ['workflow_id' => ['$in' => $workflows->keys()->all()]];

        if ($since !== null) {
            $match['classification_finished_at'] = ['$gte' => new UTCDateTime(Carbon::parse($since, 'UTC'))];
        }

        $pipeline = [
            ['$match' => $match],
            ['$group' => [
                '_id' => [
                    'workflow' => '$workflow_id',
                    'day' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$classification_finished_at']],
                ],
                'count' => ['$sum' => 1],
            ]],
        ];

        return collect($this->transcription->raw(fn ($collection) => $collection->aggregate($pipeline, ['allowDiskUse' => true])))
            ->filter(fn ($row) => $row['_id']['day'] !== null)
            ->map(fn ($row) => [
                'workflow_id' => (int) $row['_id']['workflow'],
                'date' => (string) $row['_id']['day'],
                'count' => (int) $row['count'],
            ])
            ->values();
    }
}
