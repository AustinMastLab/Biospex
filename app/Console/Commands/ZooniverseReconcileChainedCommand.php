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

namespace App\Console\Commands;

use App\Listeners\LabelReconciliationListener;
use App\Services\Expedition\ExpeditionService;
use App\Services\Reconcile\ReconcileService;
use App\Traits\SkipZooniverse;
use Illuminate\Console\Command;

/**
 * Class ZooniverseReconcileChainedCommand
 *
 * Runs lambda BiospexLabelReconciliation for single or multiple expeditions.
 * LabelReconciliationListener will handle the reconciliation process after it's complete
 * by running ZooniverseTranscriptionJob() and ZooniversePusherJob().
 */
class ZooniverseReconcileChainedCommand extends Command
{
    use SkipZooniverse;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zooniverse:reconcile-chain {expeditionIds?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconciles and process multiple expeditions including transcriptions and pusher.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected ExpeditionService $expeditionService,
        protected ReconcileService $reconcileService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * Triggers standard reconciliation without explanations.
     *
     * @see LabelReconciliationListener for result processing.
     */
    public function handle(): void
    {
        $expeditionIds = empty($this->argument('expeditionIds')) ?
            $this->getExpeditionIds() : $this->argument('expeditionIds');

        foreach ($expeditionIds as $expeditionId) {
            if ($this->skipReconcile($expeditionId)) {
                continue;
            }

            $this->reconcileService->sendToReconcileTriggerQueue((int) $expeditionId, false);
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
