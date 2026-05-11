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
use App\Models\User;
use App\Notifications\Generic;
use App\Services\Reconcile\ReconcileProcessAll;
use App\Services\Reconcile\ReconcileProcessExplained;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class LabelReconciliationJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $data)
    {
        $this->onQueue(config('config.queue.reconcile'));
    }

    /**
     * Execute the job.
     *
     * @throws \Exception|Throwable
     */
    public function handle(
        ReconcileProcessAll $reconcileProcessAll,
        ReconcileProcessExplained $reconcileProcessExplained,
        Expedition $expedition): void
    {
        $expeditionId = (int) $this->data['expeditionId'];
        $explanations = (bool) $this->data['explanations'];

        $record = $expedition->find($expeditionId);

        $explanations ?
            $reconcileProcessExplained->process($record) :
            $reconcileProcessAll->process($record);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $throwable): void
    {
        $attributes = [
            'subject' => t('SnsLabelReconciliationJob Failed'),
            'html' => [
                t('SnsLabelReconciliationJob failed'),
                t('Error: %s', $throwable->getMessage()),
                t('File: %s', $throwable->getFile()),
                t('Line: %s', $throwable->getLine()),
            ],
        ];

        $user = User::find(1);
        $user->notify(new Generic($attributes, true));
    }
}
