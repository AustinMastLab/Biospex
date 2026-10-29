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

use App\Models\Event;
use App\Models\User;
use App\Notifications\Generic;
use App\Services\Event\EventTranscriptionService;
use App\Services\Process\CreateReportService;
use App\Services\Transcriptions\PanoptesTranscriptionService;
use App\Traits\ButtonTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Str;
use Throwable;

/**
 * Class EventTranscriptionExportCsvJob
 */
class EventTranscriptionExportCsvJob implements ShouldQueue
{
    use ButtonTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user, protected Event $event)
    {
        $this->user = $user->withoutRelations();
        $this->event = $event->withoutRelations();
        $this->onQueue(config('config.queue.default'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        EventTranscriptionService $eventTranscriptionService,
        PanoptesTranscriptionService $panoptesTranscriptionService,
        CreateReportService $createReportService,
    ): void {

        try {
            $ids = $eventTranscriptionService->getEventClassificationIds($this->event->id);

            $transcriptions = $ids->map(function ($id) use ($panoptesTranscriptionService) {
                $transcript = $panoptesTranscriptionService->getFirst('classification_id', $id);
                // The model exposes the key as `id`; unsetting `_id` would leave it in.
                unset($transcript['id']);

                return $transcript;
            })->reject(function ($transcription) {
                return empty($transcription);
            });

            $csvFileName = Str::random().'.csv';
            $fileName = $createReportService->createCsvReport($csvFileName, $transcriptions->toArray());
            $fileButton = [];
            if ($fileName !== null) {
                $fileRoute = route('admin.downloads.report', ['file' => $fileName]);
                $fileButton = $this->createButton($fileRoute, t('Download CSV'));
            }

            $attributes = [
                'subject' => t('Event Transcription Export Complete'),
                'html' => [
                    t('Your export is completed. If a report was generated, you may click the download button to download the file. If no button is included, it is due to no records being located for the export. Some records require overnight processing before they are available.'),
                    t('If you believe this is an error, please contact the Administration.'),
                ],
                'buttons' => $fileButton,
            ];

            $this->user->notify(new Generic($attributes));
        } catch (Throwable $throwable) {
            $attributes = [
                'subject' => t('Event Transcription Export Error'),
                'html' => [
                    t('There was an error while exporting the csv file. The Administration has been copied on this error and will investigate.'),
                    t('File: %s', $throwable->getFile()),
                    t('Line: %s', $throwable->getLine()),
                    t('Message: %s', $throwable->getMessage()),
                ],
            ];
            $this->user->notify(new Generic($attributes, true));
        }
    }
}
