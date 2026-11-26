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

namespace App\Livewire;

use App\Models\WeDigBioEvent;
use App\Services\WeDigBio\WeDigBioService;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The WeDigBio progress modal's body (common/wedigbio-progress-modal.blade.php): an event's transcriptions per project,
 * read when the modal opens and every five minutes while it is open (WeDigBioService caches them in five-minute windows).
 */
class WeDigBioProgress extends Component
{
    public bool $isOpen = false;

    /**
     * The event shown, by slug; null for the current (or last completed) event.
     */
    public ?string $eventSlug = null;

    /**
     * Show an event's progress: the modal opened from that event's Progress link (its data-event).
     */
    public function open(?string $eventSlug = null): void
    {
        $this->isOpen = true;
        $this->eventSlug = $eventSlug ?: null;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->eventSlug = null;
    }

    public function render(): View
    {
        $weDigBioEvent = null;

        if ($this->isOpen) {
            $event = $this->eventSlug === null ? null : WeDigBioEvent::where('slug', $this->eventSlug)->first();
            $weDigBioEvent = app(WeDigBioService::class)->getWeDigBioEventTranscriptions($event);
        }

        return view('livewire.we-dig-bio-progress', ['weDigBioEvent' => $weDigBioEvent]);
    }
}
