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

namespace App\Services\Actor\Zooniverse;

use App\Enums\ActorExpeditionState;
use App\Models\Download;
use App\Models\Expedition;
use App\Services\Actor\ActorFactory;
use App\Services\Expedition\ExpeditionService;
use Illuminate\Support\Facades\Storage;

/**
 * Service class for handling Zooniverse export cleanup operations.
 * Manages the reset and cleanup of expedition exports and related files.
 */
class ZooniverseExportCleanupService
{
    /**
     * Constructor for ZooniverseExportCleanupService.
     *
     * @param  ExpeditionService  $expeditionService  Service for expedition operations
     * @param  Download  $download  Download model instance
     */
    public function __construct(
        protected ExpeditionService $expeditionService,
        protected Download $download
    ) {}

    /**
     * Reset an expedition's export process.
     * Deletes the export queue and resets expedition data.
     *
     * @param  int  $expeditionId  The ID of the expedition to reset
     */
    public function resetExpeditionExport(int $expeditionId): void
    {
        $expedition = $this->expeditionService->getExpeditionForQueueReset($expeditionId);

        if ($expedition->exportQueue) {
            $expedition->exportQueue->delete();
        }

        $this->resetExpeditionData($expedition);
    }

    /**
     * Reset expedition data and trigger reprocessing.
     * Deletes export files and updates expedition state.
     *
     * @param  Expedition  $expedition  The expedition to reset
     */
    private function resetExpeditionData(Expedition $expedition): void
    {
        $this->deleteExportFiles($expedition->id);

        $attributes = [
            'state' => $expedition->zooActorExpedition->state === ActorExpeditionState::NotStarted ? ActorExpeditionState::Exported : $expedition->zooActorExpedition->state,
            'total' => $expedition->stat->local_subject_count,
        ];

        $expedition->zooActorExpedition->update($attributes);
        $expedition->zooActorExpedition->state = ActorExpeditionState::Exported;

        ActorFactory::create($expedition->zooActorExpedition->actor->class)->process($expedition->zooActorExpedition);
    }

    /**
     * Delete export files associated with an expedition.
     * Removes files from S3 storage and deletes download records.
     *
     * @param  string  $expeditionId  The ID of the expedition
     */
    private function deleteExportFiles(string $expeditionId): void
    {
        $downloads = $this->download->where('actor_id', config('zooniverse.actor_id'))->where('expedition_id',
            $expeditionId)->where('type', 'export')->get();

        $downloads->each(function ($download) {
            $paths = [
                config('config.export_dir').'/'.$download->file, config('config.report_dir').'/'.$download->file,
            ];

            foreach ($paths as $path) {
                if (Storage::disk('s3')->exists($path)) {
                    Storage::disk('s3')->delete($path);
                }
            }

            $download->delete();
        });
    }
}
