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
use App\Jobs\ZooniverseCsvJob;
use App\Jobs\ZooniverseExportBuildQueueJob;
use App\Models\ActorExpedition;

/**
 * Class Zooniverse
 */
class Zooniverse
{
    /**
     * Process export job.
     *
     * State = 0: Expedition created.
     * State = 1: Export for Expedition. Set to 1 when export called and performed. @see \App\Console\Commands\ExportQueueCommand
     * State = 2: Will not run until process started and set to 2, added to WorkflowManager. @see \App\Http\Controllers\Admin\WorkflowManagerController
     * State = 3: Zooniverse classifications completed. @see \App\Console\Commands\ZooniverseClassificationCountCommand
     *
     * The export's own stages are listed in \App\Enums\ExportQueueStage.
     *
     * @throws \Throwable
     */
    public function process(ActorExpedition $actorExpedition): void
    {
        if ($actorExpedition->state === ActorExpeditionState::Exported) {
            // @see \App\Console\Commands\ExportQueueCommand
            ZooniverseExportBuildQueueJob::dispatch($actorExpedition);
        } elseif ($actorExpedition->state === ActorExpeditionState::Processing && config('zooniverse.enabled')) {
            ZooniverseCsvJob::dispatch($actorExpedition->expedition_id);
        }
    }
}
