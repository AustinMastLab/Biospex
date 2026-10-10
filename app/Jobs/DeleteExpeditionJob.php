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
use App\Services\MongoDbService;
use App\Services\Subject\SubjectService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Class DeleteExpeditionJob
 */
class DeleteExpeditionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user, protected Expedition $expedition)
    {
        $this->user = $user->withoutRelations();
        $this->expedition = $expedition->withoutRelations();
        $this->onQueue(config('config.queue.default'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        SubjectService $subjectService,
        MongoDbService $mongoDbService
    ): void {

        $this->expedition->load('downloads');

        $this->expedition->downloads->each(function ($download) {
            $path = config('config.export_dir').'/'.$download->file;

            try {
                // We check existence to avoid unnecessary API calls,
                // but we catch the failure because S3 consistency is not guaranteed.
                if (Storage::disk('s3')->exists($path)) {
                    Storage::disk('s3')->delete($path);
                }
            } catch (Throwable $e) {
                // If the file is already gone or inaccessible, we continue.
                // This prevents the "UnableToDeleteFile" exception from failing the job.
                Log::warning('S3 Delete failed but continuing: '.$path);
            }
        });

        $mongoDbService->setCollection('pusher_transcriptions');
        $mongoDbService->deleteMany(['expedition_uuid' => $this->expedition->uuid]);

        $mongoDbService->setCollection('panoptes_transcriptions');
        $mongoDbService->deleteMany(['subject_expeditionId' => $this->expedition->id]);

        $subjectIds = $subjectService->findByExpeditionId((int) $this->expedition->id, ['_id'])->pluck('_id');

        if ($subjectIds->isNotEmpty()) {
            $subjectService->detachSubjects($subjectIds, $this->expedition->id);
        }

        $this->expedition->delete();

        $attributes = [
            'subject' => t('Records Deleted'),
            'html' => [
                t('Expedition `%s` and all corresponding records have been deleted.', $this->expedition->title),
            ],
        ];

        $this->user->notify(new Generic($attributes));
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $throwable): void
    {
        $attributes = [
            'subject' => t('Delete Expedition Job Failed'),
            'html' => [
                t('Error: Could not delete Expedition %s', $this->expedition->title),
                t('File: %s', $throwable->getFile()),
                t('Line: %s', $throwable->getLine()),
                t('Message: %s', $throwable->getMessage()),
                t('The Administration has been notified. If you are unable to resolve this issue, please contact the Administration.'),
            ],
        ];
        $this->user->notify(new Generic($attributes, true));
    }
}
