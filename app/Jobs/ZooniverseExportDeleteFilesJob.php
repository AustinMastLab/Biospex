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

use App\Models\ExportQueue;
use App\Traits\NotifyOnJobFailure;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Class ZooniverseExportDeleteFilesJob
 */
class ZooniverseExportDeleteFilesJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, NotifyOnJobFailure, Queueable, SerializesModels;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(protected ExportQueue $exportQueue)
    {
        $this->exportQueue = $exportQueue->withoutRelations();
        $this->onQueue(config('config.queue.export'));
    }

    /**
     * Execute the job.
     *
     * @throws \Exception
     */
    public function handle(): void
    {
        $processDir = config('config.scratch_dir')."/{$this->exportQueue->id}-".config('zooniverse.actor_id')."-{$this->exportQueue->expedition->uuid}";
        if (Storage::disk('s3')->exists($processDir)) {
            Storage::disk('s3')->deleteDirectory($processDir);
        }

        $this->exportQueue->delete();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $throwable): void
    {
        $this->exportQueue->error = 1;
        $this->exportQueue->save();

        $this->notifyGroupOnFailure($this->exportQueue, $throwable);
    }
}
