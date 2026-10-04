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

use App\Models\EventTranscription;
use App\Models\EventUser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Class EventTranscriptionService
 * TODO: Try to break into smaller classes to avoid DI count.
 */
class EventTranscriptionService
{
    /**
     * EventTranscriptionService constructor.
     */
    public function __construct(
        protected EventService $eventService,
        protected EventTranscription $eventTranscription,
        protected EventUser $eventUser,
        protected Carbon $carbon
    ) {}

    /**
     * Create event transcription for user.
     */
    public function createEventTranscription(
        int $classification_id,
        int $projectId,
        string $userName,
        ?Carbon $date = null
    ): bool {
        $user = $this->eventUser->where('nfn_user', $userName)->first(['id']);

        if ($user === null) {
            return false;
        }

        $timestamp = ! isset($date) ? $this->carbon::now('UTC') : $date;

        $events = $this->eventService->getAnyEventsForUserByProjectIdAndDate($projectId, $user->id, $timestamp->toDateTimeString());

        $events->each(function ($event) use ($classification_id, $user, $timestamp) {
            $event->teams->each(function ($team) use ($event, $classification_id, $user, $timestamp) {
                $attributes = [
                    'classification_id' => $classification_id,
                    'event_id' => $event->id,
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                ];

                // The unique index on these attributes makes this safe against concurrent or retried jobs.
                $this->eventTranscription->createOrFirst($attributes, [
                    'created_at' => $timestamp->toDateTimeString(),
                    'updated_at' => $timestamp->toDateTimeString(),
                ]);
            });
        });

        return $events->isNotEmpty();
    }

    /**
     * Get event classification ids.
     */
    public function getEventClassificationIds($eventId): mixed
    {
        return $this->eventTranscription->where('event_id', $eventId)->pluck('classification_id');
    }

    /**
     * Get transcriptions for event step chart.
     */
    public function getEventRateChartTranscriptions(int $eventId, Carbon $startLoad, Carbon $endLoad): ?Collection
    {
        $key = 'event_rate_chart_transcriptions:'.$eventId.':'.md5($startLoad->toDateTimeString().$endLoad->toDateTimeString());
        $tags = ['events', 'transcriptions', 'rate_charts', 'teams'];

        return Cache::tags($tags)->remember($key, 1800, function () use ($eventId, $startLoad, $endLoad) {
            return $this->eventTranscription->with('team:id,title')
                ->selectRaw('event_id, ADDTIME(FROM_UNIXTIME(FLOOR((UNIX_TIMESTAMP(created_at))/300)*300), "0:05:00") AS time, team_id, count(id) as count')
                ->where('event_id', $eventId)
                ->where('created_at', '>=', $startLoad->toDateTimeString())
                ->where('created_at', '<', $endLoad->toDateTimeString())
                ->groupBy(['time', 'team_id', 'event_id'])->orderBy('time')->get();
        });
    }
}
