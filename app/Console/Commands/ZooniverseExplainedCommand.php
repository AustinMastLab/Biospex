<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
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

namespace App\Console\Commands;

use App\Services\Expedition\ExpeditionService;
use App\Services\Reconcile\ReconcileService;
use App\Traits\SkipZooniverse;
use Illuminate\Console\Command;

/**
 * Class ZooniverseExplainedCommand
 *
 * Sends expeditions to the environment-specific reconcile trigger queue
 * with explanations enabled.
 */
class ZooniverseExplainedCommand extends Command
{
    use SkipZooniverse;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zooniverse:explained {expeditionIds?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queues explained reconciliation for one or more expeditions.';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected ExpeditionService $expeditionService,
        protected ReconcileService $reconcileService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $expeditionIds = empty($this->argument('expeditionIds'))
            ? $this->getExpeditionIds()
            : $this->argument('expeditionIds');

        foreach ($expeditionIds as $expeditionId) {
            if ($this->skipReconcile($expeditionId)) {
                continue;
            }

            $this->reconcileService->sendToReconcileTriggerQueue((int) $expeditionId, true);
        }
    }

    /**
     * Get all expeditions for process if no ids are passed.
     */
    private function getExpeditionIds(): array
    {
        $expeditions = $this->expeditionService->getExpeditionsForZooniverseProcess();

        return $expeditions->pluck('id')->toArray();
    }
}

