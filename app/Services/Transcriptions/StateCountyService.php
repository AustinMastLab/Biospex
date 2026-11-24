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

use App\Models\StateCounty;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StateCountyService
{
    public function __construct(protected StateCounty $model) {}

    /**
     * Create.
     */
    public function create(array $data): mixed
    {
        return $this->model->create($data);
    }

    /**
     * Each state's transcription count for a project's map on its public page.
     *
     * Cached, and rebuilt every four hours by app:update-participant-counts: counting reads all of a project's
     * transcription locations, which takes seconds for a large project.
     *
     * @return Collection<int, array{id: string, value: int, name: string, statenum: int}>
     */
    public function getStateTranscriptCount(int $projectId): Collection
    {
        return Cache::rememberForever($this->stateCountKey($projectId), fn () => $this->countStateTranscriptions($projectId));
    }

    /**
     * Count a project's transcriptions per state again and store the result.
     */
    public function refreshStateTranscriptCount(int $projectId): void
    {
        Cache::forever($this->stateCountKey($projectId), $this->countStateTranscriptions($projectId));
    }

    /**
     * Ids of the projects that have transcription locations.
     *
     * @return array<int, int>
     */
    public function getLocatedProjectIds(): array
    {
        return DB::table('transcription_locations')->distinct()->orderBy('project_id')->pluck('project_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * One grouped query counts the project's transcription locations by state; every state is listed, with 0 where
     * the project has none, as the map expects.
     *
     * @return Collection<int, array{id: string, value: int, name: string, statenum: int}>
     */
    protected function countStateTranscriptions(int $projectId): Collection
    {
        $counts = DB::table('transcription_locations')
            ->join('state_counties', 'state_counties.id', '=', 'transcription_locations.state_county_id')
            ->where('transcription_locations.project_id', $projectId)
            ->groupBy('state_counties.state_num')
            ->pluck(DB::raw('count(*)'), 'state_counties.state_num');

        return $this->model->newQuery()
            ->whereNotNull('state_num')
            ->where('state_num', '!=', '')
            ->get(['state_num', 'state_abbr_cap'])
            ->groupBy('state_num')
            ->map(fn ($counties, $stateNum) => [
                'id' => 'US-'.$counties->first()->state_abbr_cap,
                'value' => (int) ($counts[$stateNum] ?? 0),
                'name' => $counties->first()->state_abbr_cap,
                'statenum' => $counties->first()->state_num,
            ])
            ->values();
    }

    protected function stateCountKey(int $projectId): string
    {
        return "project_state_counts:{$projectId}";
    }

    /**
     * Get county transcription count for project.
     */
    public function getCountyTranscriptionCount($projectId, $stateId): mixed
    {
        return $this->model->withCount(['transcriptionLocations' => function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        }])->where('state_num', $stateId)->get();
    }

    /**
     * Find by county, state.
     */
    public function findByCountyState($county, $stateAbbr): mixed
    {
        return $this->model->where('county_name', 'like', '%'.$county.'%')->where('state_abbr', $stateAbbr)->first();
    }

    /**
     * Truncate database table.
     *
     * @throws \Exception
     */
    public function truncate(): void
    {
        if (\App::isProduction()) {
            throw new \Exception('Cannot truncate database table in production.');
        }

        $this->model->truncate();
    }
}
