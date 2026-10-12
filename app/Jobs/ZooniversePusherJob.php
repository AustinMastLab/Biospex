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

use App\Models\Expedition;
use App\Models\User;
use App\Notifications\Generic;
use App\Services\Event\EventTranscriptionService;
use App\Services\Transcriptions\UpdateOrCreatePusherTranscriptionService;
use App\Services\WeDigBio\WeDigBioTranscriptionService;
use App\Traits\SkipZooniverse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class ZooniversePusherJob
 */
class ZooniversePusherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SkipZooniverse;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Expedition $expedition, protected ?int $days = null)
    {
        $this->expedition = $expedition->withoutRelations();
        $this->onQueue(config('config.queue.reconcile'));
    }

    /**
     * Creates or updates existing pusher transcriptions from overnight scripts.
     * Creates event transcripting in mysql for user if one does not exist.
     *
     * TODO: Perhaps break this out into two jobs instead
     *
     * @throws \Exception
     */
    public function handle(
        UpdateOrCreatePusherTranscriptionService $updateOrCreatePusherTranscriptionService,
        EventTranscriptionService $eventTranscriptionService,
        WeDigBioTranscriptionService $weDigBioTranscriptionService): void
    {
        if ($this->skipReconcile($this->expedition->id)) {
            $this->delete();

            return;
        }

        $this->expedition->load('panoptesProject');

        $timestamp = isset($this->days) ? Carbon::now()->subDays($this->days) : Carbon::now()->subDays(3);

        $transcriptions = $updateOrCreatePusherTranscriptionService->getTranscriptions($this->expedition->id, $timestamp);

        $transcriptions->each(function ($transcription) use (
            $updateOrCreatePusherTranscriptionService,
            $eventTranscriptionService,
            $weDigBioTranscriptionService) {

            try {
                $updateOrCreatePusherTranscriptionService->processTranscripts($transcription, $this->expedition);

                $scoreboard = $eventTranscriptionService->createEventTranscription($transcription->classification_id, $this->expedition->project_id, $transcription->user_name, $transcription->classification_finished_at);

                if ($scoreboard) {
                    ScoreboardJob::dispatch($this->expedition->project_id);
                }

                $weDigBioTranscriptionService->createEventTranscription($transcription->classification_id, $this->expedition->project_id, $transcription->classification_finished_at);
            } catch (Throwable $e) {
                Log::error('Failed to process individual transcription in ZooniversePusherJob', [
                    'expedition_id' => $this->expedition->id,
                    'classification_id' => $transcription->classification_id ?? 'unknown',
                    'user_name' => $transcription->user_name ?? 'unknown',
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                ]);

                // Skip this transcription and continue with the next one
                return;
            }
        });
    }

    /**
     * Prevent job overlap using expeditionId.
     *
     * @return WithoutOverlapping[]
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping($this->expedition->id)];
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $throwable): void
    {
        $attributes = [
            'subject' => t('Zooniverse Pusher Job Failed'),
            'html' => [
                t('File: %s', $throwable->getFile()),
                t('Line: %s', $throwable->getLine()),
                t('Message: %s', $throwable->getMessage()),
            ],
        ];

        $user = User::find(config('config.admin.user_id'));
        $user->notify(new Generic($attributes));
    }
}
