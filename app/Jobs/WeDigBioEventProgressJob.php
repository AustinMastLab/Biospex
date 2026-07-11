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

use App\Events\WeDigBioProgressEvent;
use App\Models\WeDigBioEvent;
use App\Services\WeDigBio\WeDigBioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use View;

/**
 * Class WeDigBioEventProgressJob
 */
class WeDigBioEventProgressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     * Null is passed to the event parameter if using Nav links that result in active WeDigBio Event.
     * Assigns zero to the channel.
     */
    public function __construct(public ?WeDigBioEvent $event = null)
    {
        $this->event = $event->withoutRelations();
        $this->onQueue(config('config.queue.event'));
    }

    /**
     * Handle Job.
     */
    public function handle(WeDigBioService $weDigBioService): void
    {
        $weDigBioEvent = $weDigBioService->getWeDigBioEventTranscriptions($this->event);
        $channelKey = is_null($this->event) ? '0' : (string) ($weDigBioEvent->channel_key ?? $weDigBioEvent->slug ?? '0');

        $data = [$channelKey => View::make('common.wedigbio-progress-content', compact('weDigBioEvent'))->render()];

        WeDigBioProgressEvent::dispatch($channelKey, $data);
    }
}
