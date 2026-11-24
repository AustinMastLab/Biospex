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

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use App\Notifications\Generic;
use App\Services\Chart\ProjectTranscriptionChart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\ResponseCache\Facades\ResponseCache;
use Throwable;

/**
 * Brings a project's Digitizations chart data up to date (from the last stored day) after its expeditions'
 * classification counts change, and clears the cached public pages that show it.
 */
class RefreshProjectChartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    public function __construct(protected Project $project)
    {
        $this->project = $project->withoutRelations();
        $this->onQueue(config('config.queue.chart'));
    }

    public function handle(ProjectTranscriptionChart $chart): void
    {
        $chart->refresh($this->project);

        if (config('responsecache.enabled')) {
            ResponseCache::clear();
        }
    }

    public function failed(Throwable $throwable): void
    {
        $attributes = [
            'subject' => t('RefreshProjectChartJob failed'),
            'html' => [
                t('File: %s', $throwable->getFile()),
                t('Line: %s', $throwable->getLine()),
                t('Message: %s', $throwable->getMessage()),
            ],
        ];

        User::find(config('config.admin.user_id'))?->notify(new Generic($attributes));
    }
}
