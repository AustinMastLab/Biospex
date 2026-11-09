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
use App\Services\Api\PanoptesApiService;
use App\Services\Event\EventTranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class EventTranscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    public int $tries = 3;

    /**
     * Seconds to wait before each retry, so a short Panoptes outage can pass.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public array $data, public int $expeditionId)
    {
        $this->onQueue(config('config.queue.biospex_event'));
    }

    /**
     * Execute the job.
     */
    public function handle(Expedition $expedition, PanoptesApiService $apiService, EventTranscriptionService $eventTranscriptionService): void
    {
        $expedition = $expedition->find($this->expeditionId);
        $user = $this->data['user_id'] !== null ? $apiService->getPanoptesUser($this->data['user_id']) : null;

        if ($expedition === null || $user === null) {
            $this->delete();

            return;
        }

        $scoreboard = $eventTranscriptionService->createEventTranscription((int) $this->data['classification_id'], $expedition->project_id, $user['login']);

        if ($scoreboard) {
            ScoreboardJob::dispatch($expedition->project_id);
        }
    }
}
