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

namespace App\Jobs;

use App\Services\Api\PanoptesApiService;
use App\Services\Transcriptions\PusherTranscriptionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job to process Zooniverse classifications and dispatch them for further processing.
 *
 * This job receives classification data from Zooniverse, enriches it with additional
 * subject and user information from the Panoptes API, and prepares it for storage
 * by building a standardized payload format.
 *
 * @implements ShouldQueue
 */
class PusherClassificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    /**
     * Seconds to wait before each retry, so a short Panoptes outage can pass.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60];

    /**
     * Create a new job instance.
     *
     * @param  array  $data  The classification data from Zooniverse
     * @param  string  $title  The project title
     */
    public function __construct(
        protected array $data,
        protected string $title
    ) {
        $this->onQueue(config('config.queue.pusher_handler'));
    }

    /**
     * Execute the job.
     *
     * Retrieves additional data from Panoptes API and dispatches enriched data for storage.
     *
     * @param  PanoptesApiService  $apiService  Service for interacting with Panoptes API
     * @param  PusherTranscriptionService  $transcriptionService  Service for handling transcriptions
     */
    public function handle(
        PanoptesApiService $apiService,
        PusherTranscriptionService $transcriptionService
    ): void {
        $subjectId = $this->data['subject_ids'][0] ?? null;
        $subject = $subjectId ? Cache::remember(
            "panoptes_subject_{$subjectId}",
            3600, // Cache for 1 hour
            fn () => $apiService->getPanoptesSubject($subjectId)
        ) : null;

        $user = $this->data['user_id'] !== null ? $this->getUser($apiService) : null;

        if ($subject === null) {
            return;
        }

        $payload = $this->buildDashboardPayload($subject, $user);

        // Dispatch enriched data to MongoDB
        PusherTranscriptionJob::dispatch($payload);
    }

    /**
     * Look up the volunteer on Panoptes.
     *
     * The user only adds the transcriber's login to the payload, so a failed
     * lookup (for example a Zooniverse 502) is logged and the classification
     * is saved without it instead of failing the job.
     *
     * @return array<string, mixed>|null
     */
    private function getUser(PanoptesApiService $apiService): ?array
    {
        try {
            return Cache::remember(
                "panoptes_user_{$this->data['user_id']}",
                3600, // Cache for 1 hour
                fn () => $apiService->getPanoptesUser($this->data['user_id'])
            );
        } catch (Throwable $e) {
            Log::warning('Panoptes user lookup failed; saving the classification without user details', [
                'user_id' => $this->data['user_id'],
                'classification_id' => $this->data['classification_id'],
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build the payload for dashboard display and storage.
     *
     * @param  array  $subject  The subject data from Panoptes API
     * @param  array|null  $user  The user data from Panoptes API
     * @return array The formatted payload containing classification details
     */
    private function buildDashboardPayload(array $subject, ?array $user): array
    {
        return [
            'classification_id' => (int) $this->data['classification_id'],
            'project' => $this->title,
            'description' => 'Classification Id '.$this->data['classification_id'],
            'guid' => \Str::uuid()->toString(),
            'timestamp' => Carbon::now(),
            'subject' => [
                'link' => $subject['metadata']['references'] ?? '',
                'thumbnailUri' => $this->getThumbnailUri(),
            ],
            'contributor' => [
                'decimalLatitude' => $this->data['geo']['latitude'] ?? 0,
                'decimalLongitude' => $this->data['geo']['longitude'] ?? 0,
                'ipAddress' => '',
                'transcriber' => $user['login'] ?? '',
                'physicalLocation' => [
                    'country' => $this->data['geo']['country_name'] ?? '',
                    'province' => '',
                    'county' => '',
                    'municipality' => $this->data['geo']['city_name'] ?? '',
                    'locality' => '',
                ],
            ],
            'transcriptionContent' => [
                'lat' => '',
                'long' => '',
                'country' => $subject['metadata']['country'] ?? '',
                'province' => $subject['metadata']['stateProvince'] ?? '',
                'county' => $subject['metadata']['county'] ?? '',
                'municipality' => '',
                'locality' => '',
                'date' => '',
                'collector' => '',
                'taxon' => $subject['metadata']['scientificName'] ?? '',
            ],
            'discretionaryState' => 'Transcribed',
        ];
    }

    /**
     * Extract the thumbnail URI from the classification data.
     *
     * @return string|null The URI of the thumbnail image, or null if not found
     */
    private function getThumbnailUri(): ?string
    {
        $imageUrl = $this->data['subject_urls'][0] ?? [];

        return $imageUrl['image/jpeg'] ?? $imageUrl['image/png'] ?? null;
    }
}
