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

use App\Models\Group;
use App\Notifications\Generic;
use App\Services\MongoDbService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Class DeleteGroupJob
 */
class DeleteGroupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Group $group)
    {
        $this->group = $group->withoutRelations();
        $this->onQueue(config('config.queue.default'));
    }

    /**
     * Execute the job.
     */
    public function handle(MongoDbService $mongoDbService): void
    {
        $this->group->load(['projects.expeditions.downloads', 'geoLocateForms', 'owner']);

        $this->group->projects->each(function ($project) use ($mongoDbService) {
            $project->expeditions->each(function ($expedition) use ($mongoDbService) {
                $expedition->downloads->each(function ($download) {
                    Storage::disk('s3')->delete(config('config.export_dir').'/'.$download->file);
                });

                $mongoDbService->setCollection('panoptes_transcriptions');
                $mongoDbService->deleteMany(['subject_expeditionId' => $expedition->id]);

                $mongoDbService->setCollection('transcriptions');
                $mongoDbService->deleteMany(['expedition_id' => $expedition->id]);

                $expedition->delete();
            });

            $mongoDbService->setCollection('subjects');
            $mongoDbService->deleteMany(['project_id' => $project->id]);

            // Delete through Eloquent so project observers (e.g. logo removal) run;
            // the database cascade from the group delete fires no model events.
            $project->delete();
        });

        $this->group->delete();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $throwable): void
    {
        $attributes = [
            'subject' => t('Delete Group Job Failed'),
            'html' => [
                t('Error: Could not delete Group %s', $this->group->title),
                t('File: %s', $throwable->getFile()),
                t('Line: %s', $throwable->getLine()),
                t('Message: %s', $throwable->getMessage()),
                t('The Administration has been notified. If you are unable to resolve this issue, please contact the Administration.'),
            ],
        ];

        $this->group->owner->notify(new Generic($attributes, true));
    }
}
