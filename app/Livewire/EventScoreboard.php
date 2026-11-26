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

use App\Models\Event;
use App\Services\Event\EventScoreboardService;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The scoreboard modal's body (common/scoreboard.blade.php): an event's transcriptions per team, read when the modal
 * opens and every five minutes while it is open.
 */
class EventScoreboard extends Component
{
    /**
     * The event shown, by uuid; null while the modal is closed.
     */
    public ?string $eventUuid = null;

    /**
     * Show an event's scoreboard: the modal opened from that event's Scoreboard button (its data-event).
     */
    public function open(string $eventUuid): void
    {
        $this->eventUuid = $eventUuid;
    }

    public function close(): void
    {
        $this->eventUuid = null;
    }

    public function render(): View
    {
        $event = $this->eventUuid === null ? null : Event::where('uuid', $this->eventUuid)->first();

        if ($event !== null) {
            app(EventScoreboardService::class)->getEventScoreboard($event);
        }

        return view('livewire.event-scoreboard', ['event' => $event]);
    }
}
