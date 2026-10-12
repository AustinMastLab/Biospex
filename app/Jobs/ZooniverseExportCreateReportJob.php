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

use App\Enums\ExportQueueStage;
use App\Models\ExportQueue;
use App\Models\ExportQueueFile;
use App\Notifications\Generic;
use App\Services\Process\CreateReportService;
use App\Traits\ButtonTrait;
use App\Traits\NotifyOnJobFailure;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use Throwable;

/**
 * Job to create error reports for Zooniverse exports and notify users.
 *
 * This job handles creating CSV error reports for failed Zooniverse exports,
 * notifies relevant users about the export completion, and triggers cleanup.
 */
class ZooniverseExportCreateReportJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable, ButtonTrait, Dispatchable, InteractsWithQueue, NotifyOnJobFailure, Queueable, SerializesModels;

    public int $timeout = 900;

    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     *
     * @param  ExportQueue  $exportQueue  The export queue to process
     */
    public function __construct(protected ExportQueue $exportQueue)
    {
        $this->exportQueue = $exportQueue->withoutRelations();
        $this->onQueue(config('config.queue.export'));
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    /**
     * Execute the job.
     *
     * Creates error report CSV, notifies users, and triggers a cleanup process.
     *
     * @param  CreateReportService  $createReportService  Service to create CSV reports
     *
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function handle(CreateReportService $createReportService): void
    {
        $this->exportQueue->load([
            'expedition.project.group' => function ($q) {
                $q->with(['owner', 'users' => function ($q) {
                    $q->where('notification', 1);
                }]);
            },
        ]);

        // === CREATE ERROR REPORT ===
        $data = ExportQueueFile::where('queue_id', $this->exportQueue->id)
            ->whereNotNull('message')
            ->get(['subject_id', 'message']);

        $csvName = $this->exportQueue->expedition->uuid.'.csv';
        $fileName = $createReportService->createCsvReport($csvName, $data->toArray());

        $button = [];
        if ($fileName !== null) {
            $createReportService->saveReport($this->exportQueue, $csvName);
            $route = route('admin.downloads.report', ['file' => $fileName]);
            $button = $this->createButton($route, t('Download Export Errors'), 'error');
        }

        // === NOTIFY USERS ===
        $attributes = [
            'subject' => t('Zooniverse Export Completed'),
            'html' => [
                t('The export process for "%s" has been completed successfully.', $this->exportQueue->expedition->title),
                t('If a download file was created during this process, you may access the link on the Expedition view page.'),
            ],
            'buttons' => $button,
        ];

        $users = $this->exportQueue->expedition->project->group->users
            ->push($this->exportQueue->expedition->project->group->owner);

        Notification::send($users, new Generic($attributes));

        // === FINAL: ONLY DISPATCH CLEANUP ===
        $this->exportQueue->stage = ExportQueueStage::DeletingFiles;
        $this->exportQueue->save();

        ZooniverseExportDeleteFilesJob::dispatch($this->exportQueue);
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable  $throwable  The exception that caused the failure
     */
    public function failed(Throwable $throwable): void
    {
        $this->exportQueue->error = 1;
        $this->exportQueue->save();

        $this->notifyGroupOnFailure($this->exportQueue, $throwable);
    }
}
