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

use App\Enums\ActorExpeditionState;
use App\Models\Expedition;
use App\Models\User;
use App\Notifications\Generic;
use App\Services\Api\PanoptesApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Class ZooniverseClassificationCountJob
 */
class ZooniverseClassificationCountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Expedition $expedition, protected bool $update = false)
    {
        $this->expedition = $expedition->withoutRelations();
        $this->onQueue(config('config.queue.classification'));
    }

    /**
     * Execute the job.
     */
    public function handle(PanoptesApiService $panoptesApiService): void
    {
        $this->expedition->load([
            'project.group.owner',
            'stat',
            'zooActorExpedition',
            'panoptesProject',
        ]);

        if ($this->workflowIdDoesNotExist($this->expedition)) {
            $this->delete();

            return;
        }

        $workflow = $panoptesApiService->getPanoptesWorkflow($this->expedition->panoptesProject->panoptes_workflow_id);
        $panoptesApiService->calculateTotals($workflow, $this->expedition->id);

        $this->expedition->stat->subject_count = $panoptesApiService->getSubjectCount();
        $this->expedition->stat->transcriptions_goal = $panoptesApiService->getTranscriptionsGoal();
        $this->expedition->stat->local_transcriptions_completed = $panoptesApiService->getLocalTranscriptionsCompleted();
        $this->expedition->stat->transcriptions_completed = $panoptesApiService->getTranscriptionsCompleted();
        $this->expedition->stat->transcriber_count = $panoptesApiService->getExpeditionTranscriberCount();
        $this->expedition->stat->percent_completed = $panoptesApiService->getPercentCompleted();

        $this->expedition->stat->save();

        if ($this->update) {
            return;
        }

        $this->checkFinishedAt($this->expedition, $workflow['finished_at']);

        AmChartJob::dispatch($this->expedition->project);
    }

    /**
     * Check if workflow id exists.
     */
    protected function workflowIdDoesNotExist(Expedition $expedition): bool
    {
        if ($expedition->panoptesProject === null || empty($expedition->panoptesProject->panoptes_workflow_id)) {
            return true;
        }

        return false;
    }

    /**
     * Check if finished_at date and set percentage.
     */
    protected function checkFinishedAt(Expedition $expedition, ?string $finishedAt = null): void
    {
        if ($finishedAt === null ||
            ($expedition->stat->percent_completed === 100 && $expedition->completed === 1)) {
            return;
        }

        $expedition->zooActorExpedition->state = ActorExpeditionState::Complete;
        $expedition->zooActorExpedition->save();

        $expedition->completed = 1;
        $expedition->save();

        $attributes = [
            'subject' => t('Zooniverse Transcriptions Completed'),
            'html' => [
                t('The Zooniverse digitization process for "%s" has been completed.', $expedition->title),
            ],
        ];

        $expedition->project->group->owner->notify(new Generic($attributes));
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
            'subject' => t('Zooniverse Classification Count Job Failed'),
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
