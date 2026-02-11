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

namespace App\Services\Event;

use App\Models\Event;
use App\Models\EventTeam;
use App\Models\User;
use App\Services\Helpers\DateService;
use App\Services\Trait\EventPartitionTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EventService
{
    use EventPartitionTrait;

    /**
     * EventService constructor.
     */
    public function __construct(
        protected Event $event,
        protected EventTeam $eventTeam,
        protected DateService $dateService) {}

    /**
     * Get events for admin index.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    public function getAdminIndex(User $user, array $request = []): array
    {
        $records = $user->isAdmin()
            ? $this->event->with(['project.lastPanoptesProject', 'teams:id,title,event_id'])->get()
            : $this->event->with(['project.lastPanoptesProject', 'teams:id,title,event_id'])->where('owner_id', $user->id)->get();

        $sortedRecords = $this->sortRecords($records, $request);

        return $this->partitionEvents($sortedRecords);
    }

    /**
     * Cache key for the DATA collection of public events.
     */
    protected function publicIndexDataCacheKey(array $request = []): string
    {
        $version = (int) Cache::get('public_sort:events:version', 1);

        $sort = (string) ($request['sort'] ?? 'date');
        $order = strtolower((string) ($request['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $projectId = $request['projectId'] ?? $request['id'] ?? null;

        return sprintf(
            'public_sort:events_data:v%d:locale=%s:sort=%s:order=%s:project=%s',
            $version,
            app()->getLocale(),
            $sort,
            $order,
            empty($projectId) ? 'all' : (string) $projectId,
        );
    }

    /**
     * Get cached DATA (Collection of partitions) for the public event index.
     * Must call the existing public query method and not duplicate it.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    public function getPublicIndexCachedData(array $params = []): array
    {
        // Normalize projectId
        if (isset($params['id']) && ! isset($params['projectId'])) {
            $params['projectId'] = $params['id'];
        }

        $cacheKey = $this->publicIndexDataCacheKey($params);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($params) {
            return $this->getPublicIndex($params);
        });
    }

    /**
     * Get events for public index.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    public function getPublicIndex(array $request = []): array
    {
        $sort = (string) ($request['sort'] ?? 'date');
        $order = strtolower((string) ($request['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $projectId = $request['projectId'] ?? null;

        $query = $this->event
            ->newQuery()
            ->with(['project.lastPanoptesProject', 'teams:id,title,event_id']);

        if (! empty($projectId)) {
            $query->where('project_id', $projectId);
        }

        // Sort in SQL
        if ($sort === 'project') {
            $query
                ->join('projects', 'projects.id', '=', 'events.project_id')
                ->select('events.*')
                ->orderBy('projects.title', $order);
        } elseif ($sort === 'title') {
            $query->orderBy('events.title', $order);
        } else {
            // date (default)
            $query->orderBy('events.start_date', $order);
        }

        $records = $query->get();

        return $this->partitionEvents($records);
    }

    /**
     * Sort results for index pages.
     */
    protected function sortRecords(Collection $records, array $request = []): Collection
    {
        if (! isset($request['order'])) {
            return $records;
        }

        return match ($request['sort']) {
            'title' => $request['order'] === 'desc' ? $records->sortByDesc('title') : $records->sortBy('title'),
            'project' => $request['order'] === 'desc' ? $records->sortByDesc(function ($event) {
                return $event->project->title;
            }) : $records->sortBy(function ($event) {
                return $event->project->title;
            }),
            'date' => $request['order'] === 'desc' ? $records->sortByDesc('start_date') : $records->sortBy('start_date'),
            default => $records->sortByDesc('start_date'),
        };
    }

    /**
     * Get event for show page.
     */
    public function getAdminShow(Event &$event): void
    {
        $event->loadCount('transcriptions')->load([
            'project:id,title,slug',
            'project.lastPanoptesProject:id,project_id,panoptes_project_id,panoptes_workflow_id,slug',
            'teams:id,uuid,event_id,title', 'teams.users' => function ($q) use ($event) {
                $q->withcount([
                    'transcriptions' => function ($q) use ($event) {
                        $q->where('event_id', $event->id);
                    },
                ]);
            },
        ]);
    }

    /**
     * Create event.
     */
    public function store(array $attributes): Event
    {
        $this->setEventDates($attributes);
        $event = $this->event->create($attributes);

        foreach ($attributes['teams'] as $team) {
            $team = $this->eventTeam->make($team);
            $event->teams()->save($team);
        }

        return $event;
    }

    /**
     * Get event for show page.
     */
    public function edit(Event &$event): void
    {
        $event->loadCount('transcriptions')->loadCount('teams')->load('teams:id,uuid,event_id,title');
    }

    /**
     * Overwrite model update method.
     */
    public function update(array $attributes, Event $event): bool
    {
        $this->setEventDates($attributes);

        // Get existing team IDs from database
        $existingTeamIds = $event->teams()->pluck('id')->toArray();

        // Get submitted team IDs (only those that have IDs)
        $submittedTeamIds = collect($attributes['teams'])
            ->filter(function ($team) {
                return isset($team['id']) && $team['id'] !== null;
            })
            ->pluck('id')
            ->toArray();

        // Find teams to delete (exist in database but not in submitted data)
        $teamsToDelete = array_diff($existingTeamIds, $submittedTeamIds);

        // Delete removed teams
        if (! empty($teamsToDelete)) {
            $this->eventTeam->whereIn('id', $teamsToDelete)
                ->where('event_id', $event->id)
                ->delete();
        }

        // Process submitted teams (create new or update existing)
        collect($attributes['teams'])->each(function ($team) use ($event) {
            $this->updateTeam($team, $event);
        });

        return $event->fill($attributes)->save();
    }

    /**
     * Set dates for event.
     */
    public function setEventDates(array &$data): void
    {
        $this->dateService->setEventDates($data);
    }

    /**
     * Handle team updates from event.
     */
    protected function updateTeam(array $team, Event $event): void
    {
        // Check if team has an ID and if it exists
        $record = null;
        if (isset($team['id']) && $team['id'] !== null) {
            $record = $this->eventTeam->where('id', $team['id'])->where('event_id', $event->id)->first();
        }

        // Update existing team if record found and title is not null
        if ($record && $team['title'] !== null) {
            $record->fill($team)->save();

            return;
        }

        // Delete existing team if record found and title is null
        if ($record && $team['title'] === null) {
            $record->delete();

            return;
        }

        // Create new team if no record found and title is not null
        if (! $record && $team['title'] !== null) {
            $newTeam = $this->eventTeam->make($team);
            $event->teams()->save($newTeam);
        }
    }

    /**
     * Get events by project id.
     */
    public function getEventsByProjectId($projectId): Collection
    {
        return $this->event->withCount('transcriptions')->with([
            'teams' => function ($q) {
                $q->withCount('transcriptions')->orderBy('transcriptions_count', 'desc');
            },
        ])->whereHas('teams')->where('project_id', $projectId)->get();
    }

    /**
     * Get any ongoing events for user using project id and dates.
     */
    public function getAnyEventsForUserByProjectIdAndDate(int $projectId, int $userId, string $date): \Illuminate\Database\Eloquent\Collection|array
    {
        $callback = function ($q) use ($userId) {
            $q->where('user_id', $userId);
        };

        return $this->event->with(['teams' => function ($q) use ($callback) {
            $q->whereHas('users', $callback);
            $q->with(['users' => $callback]);
        }])
            ->where('project_id', $projectId)
            ->where('start_date', '<', $date)
            ->where('end_date', '>', $date)->get();
    }
}
