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
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EventService
{
    /**
     * EventService constructor.
     */
    public function __construct(
        protected Event $event,
        protected EventTeam $eventTeam,
        protected DateService $dateService) {}

    /**
     * Get one authorization-scoped page of events for the admin index.
     */
    public function getAdminIndexPage(User $user, array $request = [], int $page = 1): Paginator
    {
        $type = ($request['type'] ?? 'active') === 'completed' ? 'completed' : 'active';
        $sort = $this->eventSortField($request['sort'] ?? 'date');
        $order = $this->eventSortOrder($request['order'] ?? 'asc');

        $query = $this->event->newQuery()
            ->with(['project.lastPanoptesProject', 'teams:id,title,event_id']);

        if (! $user->isAdmin()) {
            $query->where('events.owner_id', $user->id);
        }

        $this->applyEventType($query, $type);
        $this->applyEventOrdering($query, $sort, $order);

        return $query
            ->orderBy('events.id', $order)
            ->simplePaginate(9, ['*'], 'eventPage', $page);
    }

    /**
     * Cache key for one public event page, limited to the current minute.
     */
    protected function publicIndexPageCacheKey(array $request, int $page): string
    {
        $version = (int) Cache::get('public_sort:events:version', 1);
        $type = ($request['type'] ?? 'active') === 'completed' ? 'completed' : 'active';
        $sort = $this->eventSortField($request['sort'] ?? 'date');
        $order = $this->eventSortOrder($request['order'] ?? 'asc');
        $projectId = $request['projectId'] ?? null;

        return sprintf(
            'public_sort:events_page:v%d:locale=%s:type=%s:sort=%s:order=%s:project=%s:minute=%s:page=%d',
            $version,
            app()->getLocale(),
            $type,
            $sort,
            $order,
            empty($projectId) ? 'all' : (string) $projectId,
            now()->format('YmdHi'),
            $page,
        );
    }

    /**
     * Get one public event page for the selected filter and sort order.
     */
    public function getPublicIndexPage(array $request = [], int $page = 1): Paginator
    {
        $type = ($request['type'] ?? 'active') === 'completed' ? 'completed' : 'active';
        $sort = $this->eventSortField($request['sort'] ?? 'date');
        $order = $this->eventSortOrder($request['order'] ?? 'asc');
        $projectId = $request['projectId'] ?? null;
        $cacheKey = $this->publicIndexPageCacheKey($request, $page);

        return Cache::remember($cacheKey, now()->addMinute(), function () use ($projectId, $type, $sort, $order, $page) {
            $query = $this->event->newQuery()
                ->with(['project.lastPanoptesProject', 'teams:id,title,event_id']);

            if (! empty($projectId)) {
                $query->where('events.project_id', $projectId);
            }

            $this->applyEventType($query, $type);
            $this->applyEventOrdering($query, $sort, $order);

            return $query
                ->orderBy('events.id', $order)
                ->simplePaginate(9, ['*'], 'eventPage', $page);
        });
    }

    protected function applyEventType($query, string $type): void
    {
        $query->where('events.end_date', $type === 'completed' ? '<' : '>=', now());
    }

    protected function applyEventOrdering($query, string $sort, string $order): void
    {
        if ($sort === 'project') {
            $query->join('projects', 'projects.id', '=', 'events.project_id')
                ->select('events.*')
                ->orderBy('projects.title', $order);

            return;
        }

        $query->orderBy($sort === 'title' ? 'events.title' : 'events.start_date', $order);
    }

    protected function eventSortField(mixed $sort): string
    {
        return in_array($sort, ['title', 'project', 'date'], true) ? $sort : 'date';
    }

    protected function eventSortOrder(mixed $order): string
    {
        return strtolower((string) $order) === 'desc' ? 'desc' : 'asc';
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
