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

use App\Models\PanoptesTranscription;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use MongoDB\BSON\UTCDateTime;

class PanoptesTranscriptionService
{
    /**
     * Cache key for the stored home page counts.
     */
    public const SITE_COUNTS_KEY = 'site_counts';

    /**
     * Time limit for the counting queries. They only run from app:update-site-counts, never during a request.
     */
    protected const SITE_COUNTS_MAX_TIME_MS = 600000;

    public function __construct(protected PanoptesTranscription $model) {}

    /**
     * Create.
     */
    public function create(array $data): mixed
    {
        return $this->model->create($data);
    }

    /**
     * Get first transcription by column value.
     */
    public function getFirst(string $column, $value): mixed
    {
        return $this->model->where($column, $value)->first();
    }

    /**
     * Count the contributors and transcriptions and store them for the home page.
     *
     * Counting distinct contributors reads every transcription, which can take longer
     * than a page request may wait. If a query fails, the previously stored counts stay.
     */
    public function refreshSiteCounts(): void
    {
        $counts = [
            'contributors' => $this->countContributors(),
            'transcriptions' => $this->model->raw(function ($collection) {
                return $collection->countDocuments([], ['maxTimeMS' => self::SITE_COUNTS_MAX_TIME_MS]);
            }),
        ];

        $this->siteCountsCache()->forever(self::SITE_COUNTS_KEY, $counts);
    }

    /**
     * Stored number of contributors, or null before the counts are first stored.
     */
    public function getContributorCount(): ?int
    {
        return $this->siteCountsCache()->get(self::SITE_COUNTS_KEY)['contributors'] ?? null;
    }

    /**
     * Stored number of transcriptions, or null before the counts are first stored.
     */
    public function getTotalTranscriptions(): ?int
    {
        return $this->siteCountsCache()->get(self::SITE_COUNTS_KEY)['transcriptions'] ?? null;
    }

    /**
     * Count distinct contributors, leaving out the "not-logged-in" placeholder names.
     */
    protected function countContributors(): int
    {
        $aggregationQuery = [
            ['$sort' => ['user_name' => 1]],
            ['$group' => ['_id' => '$user_name']],
            ['$match' => ['_id' => ['$not' => ['$regex' => '^not-logged-in.*', '$options' => 'i']]]],
            ['$count' => 'count'],
        ];

        $result = $this->model->raw(function ($collection) use ($aggregationQuery) {
            return $collection->aggregate($aggregationQuery, ['maxTimeMS' => self::SITE_COUNTS_MAX_TIME_MS]);
        })->first();

        return $result === null ? 0 : $result->count;
    }

    /**
     * The cache store that keeps the home page counts across deploys.
     */
    protected function siteCountsCache(): Repository
    {
        return Cache::store(config('config.site_counts_cache_store'));
    }

    /**
     * Get expedition transcription count.
     */
    public function getExpeditionTranscriptionCount(int $expeditionId): int
    {
        $aggregationQuery = [
            ['$match' => ['subject_expeditionId' => $expeditionId]],
            ['$count' => 'count'],
        ];

        $key = "expedition_transcription_count:{$expeditionId}";
        $tags = ['panoptes_transcriptions'];

        $result = Cache::tags($tags)->remember($key, 14440, function () use ($aggregationQuery) {
            return $this->model->raw(function ($collection) use ($aggregationQuery) {
                return $collection->aggregate($aggregationQuery);
            })->first();
        });

        return $result === null ? 0 : $result->count;
    }

    /**
     * Get transcriber count for expedition.
     */
    public function getExpeditionTranscriberCount(int $expeditionId): int
    {
        $aggregationQuery = [
            [
                '$match' => ['subject_expeditionId' => $expeditionId],
            ],
            [
                '$group' => ['_id' => '$user_name'],
            ],
            ['$count' => 'count'],
        ];

        $key = "expedition_transcriber_count:{$expeditionId}";
        $tags = ['panoptes_transcriptions'];

        $result = Cache::tags($tags)->remember($key, 14440, function () use ($aggregationQuery) {
            return $this->model->raw(function ($collection) use ($aggregationQuery) {
                return $collection->aggregate($aggregationQuery);
            })->first();
        });

        return $result === null ? 0 : $result->count;
    }

    /**
     * Get transcribers transcription count.
     */
    public function getTranscribersTranscriptionCount(int $projectId): mixed
    {
        $aggregationQuery = [
            [
                '$match' => [
                    'subject_projectId' => (int) $projectId,
                ],
            ],
            [
                '$sort' => [
                    'classification_finished_at' => -1,
                ],
            ],
            [
                '$group' => [
                    '_id' => '$user_name',
                    'transcriptionCount' => [
                        '$sum' => 1,
                    ],
                    'expedition' => [
                        '$addToSet' => '$subject_expeditionId',
                    ],
                    'last_date' => [
                        '$max' => '$classification_finished_at',
                    ],
                ],
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'user_name' => '$_id',
                    'transcriptionCount' => 1,
                    'expeditionCount' => [
                        '$size' => '$expedition',
                    ],
                    'last_date' => 1,
                ],
            ],
        ];

        $key = "transcribers_transcription_count:{$projectId}";
        $tags = ['panoptes_transcriptions'];

        return Cache::tags($tags)->rememberForever($key, function () use ($aggregationQuery) {
            return $this->model->raw(function ($collection) use ($aggregationQuery) {
                return $collection->aggregate($aggregationQuery);
            });
        });
    }

    /**
     * Get transcription for dashboard.
     */
    public function getTranscriptionsForDashboardJob(int $expeditionId, $timestamp = null): mixed
    {
        $model = $this->model->with([
            'subject' => function ($query) {
                $query->select('accessURI');
            },
        ])->where('subject_expeditionId', '=', $expeditionId);

        if ($timestamp !== null) {
            $model->where('classification_finished_at', '>=', $timestamp);
        }

        return $model->orderBy('classification_finished_at')->get();
    }

    /**
     * Get minimum finish date of transcriptions for project.
     */
    public function getMinFinishedAtDateByProjectId(int $projectId): mixed
    {
        $aggregationQuery = [
            ['$match' => ['subject_projectId' => (int) $projectId]],
            ['$sort' => ['classification_finished_at' => 1]],
            ['$limit' => 1],
        ];

        $key = "project_transcriber_count:{$projectId}";
        $tags = ['panoptes_transcriptions'];

        $result = Cache::tags($tags)->remember($key, 14440, function () use ($aggregationQuery) {
            return $this->model->raw(function ($collection) use ($aggregationQuery) {
                return $collection->aggregate($aggregationQuery);
            })->first();
        });

        return $result?->classification_finished_at->format('Y-m-d H:i:s');
    }

    /**
     * Get maximum finish date of transcriptions for project.
     * TODO check query because it gave error (projectId 62) could not send aggregate
     *
     * @return mixed|null
     */
    public function getMaxFinishedAtDateByProjectId(int $projectId)
    {
        $aggregationQuery = [
            ['$match' => ['subject_projectId' => (int) $projectId]],
            ['$sort' => ['classification_finished_at' => -1]],
            ['$limit' => 1],
        ];

        $key = "min_finished_date:{$projectId}";
        $tags = ['panoptes_transcriptions'];

        $result = Cache::tags($tags)->remember($key, 14440, function () use ($aggregationQuery) {
            return $this->model->raw(function ($collection) use ($aggregationQuery) {
                return $collection->aggregate($aggregationQuery);
            })->first();
        });

        return $result?->classification_finished_at->format('Y-m-d H:i:s');
    }

    /**
     * Get transcription count and group by date.
     */
    public function getTranscriptionCountPerDate(int $workflowId, $begin, $end): mixed
    {
        $aggregationQuery = [
            [
                '$match' => [
                    'workflow_id' => $workflowId,
                    'classification_finished_at' => [
                        '$gte' => new UTCDateTime($begin),
                        '$lt' => new UTCDateTime($end),
                    ],
                ],
            ],
            [
                '$project' => [
                    'yearMonthDay' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d',
                            'date' => '$classification_finished_at',
                        ],
                    ],
                ],
            ],
            ['$group' => ['_id' => '$yearMonthDay', 'count' => ['$sum' => 1]]],
            ['$sort' => ['_id' => 1]],
        ];

        $bindings = [
            'workflow_id' => $workflowId,
            'begin' => $begin->__toString(),
            'end' => $end->__toString(),
        ];

        $key = "transcription_count_per_date:{$workflowId}:".md5($begin->__toString().$end->__toString());
        $tags = ['panoptes_transcriptions'];

        return Cache::tags($tags)->remember($key, 14400, function () use ($aggregationQuery) {
            return $this->model->raw(function ($collection) use ($aggregationQuery) {
                return $collection->aggregate($aggregationQuery);
            })->pluck('count', '_id');
        });
    }

    /**
     * Return count for transcriber.
     */
    public function getTranscriptionCountForTranscriber(int $projectId, string $transcriber): int
    {
        return $this->model->where('subject_projectId', $projectId)->where('user_name', $transcriber)->count();
    }

    /**
     * Get transcriber count for project.
     */
    public function getProjectTranscriberCount(int $projectId): int
    {
        $aggregationQuery = [
            [
                '$match' => ['subject_projectId' => (int) $projectId],
            ],
            [
                '$group' => ['_id' => '$user_name'],
            ],
            ['$count' => 'count'],
        ];

        $key = "max_finished_date:{$projectId}";
        $tags = ['panoptes_transcriptions'];

        $result = Cache::tags($tags)->remember($key, 14440, function () use ($aggregationQuery) {
            return $this->model->raw(function ($collection) use ($aggregationQuery) {
                return $collection->aggregate($aggregationQuery);
            })->first();
        });

        return $result === null ? 0 : $result->count;
    }
}
