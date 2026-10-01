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

use App\Models\ExpeditionSaveRequest;
use App\Notifications\Generic;
use App\Services\Expedition\ExpeditionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ExpeditionSaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 7200;

    public int $tries = 3;

    public function __construct(protected int $expeditionId)
    {
        $this->onQueue(config('config.queue.expedition_save'));
    }

    public function handle(ExpeditionService $expeditionService): void
    {
        $saveRequest = ExpeditionSaveRequest::query()
            ->with(['expedition', 'user'])
            ->where('expedition_id', $this->expeditionId)
            ->first();

        if ($saveRequest === null || $saveRequest->expedition === null || $saveRequest->user === null) {
            return;
        }

        $revision = $saveRequest->revision;
        $saveRequest->update(['status' => 'processing']);

        $expedition = $saveRequest->expedition;
        $subjectCount = $expeditionService->synchronizeSubjects(
            $expedition,
            collect($saveRequest->subject_ids)
        );

        $saveRequest->refresh();

        if ($saveRequest->revision !== $revision) {
            $this->release(0);

            return;
        }

        $expedition->load(['actors', 'project', 'workflow.actors.contacts', 'workflowManager']);
        $expeditionService->syncStat($expedition, $subjectCount);
        $expeditionService->syncActors($expedition, $subjectCount);

        if ($saveRequest->operation === 'create') {
            $expeditionService->notifyActorContacts($expedition, $expedition->project);
        }

        $saveRequest->user->notify(new Generic([
            'subject' => t('Expedition Subject Assignment Complete'),
            'html' => [
                t('Subject assignment for Expedition `%s` has been completed successfully.', $expedition->title),
            ],
        ]));

        $saveRequest->delete();
    }

    /**
     * Prevent simultaneous subject updates for the same expedition.
     *
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('expedition-save-'.$this->expeditionId)];
    }

    public function failed(Throwable $throwable): void
    {
        $saveRequest = ExpeditionSaveRequest::query()
            ->with(['expedition', 'user'])
            ->where('expedition_id', $this->expeditionId)
            ->first();

        if ($saveRequest === null || $saveRequest->user === null) {
            return;
        }

        $saveRequest->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        $saveRequest->user->notify(new Generic([
            'subject' => t('Expedition Subject Assignment Failed'),
            'html' => [
                t('Subject assignment for Expedition `%s` could not be completed.', $saveRequest->expedition?->title ?? $this->expeditionId),
                t('The Administration has been notified. If you are unable to resolve this issue, please contact the Administration.'),
            ],
        ], true));
    }
}
