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

namespace App\Livewire\Front;

use App\Services\Event\EventService;
use Livewire\Component;

class EventsIndex extends Component
{
    public string $type = 'active';

    public string $sort = 'date';

    public string $order = 'asc';

    public ?int $projectId = null;

    public function mount(?string $type = null, ?int $projectId = null): void
    {
        if ($type !== null) {
            $this->type = in_array($type, ['active', 'completed'], true) ? $type : 'active';
        }

        if ($projectId !== null) {
            $this->projectId = $projectId;
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->order = 'asc';
        }
    }

    public function setType(string $type): void
    {
        $this->type = in_array($type, ['active', 'completed'], true) ? $type : 'active';
    }

    public function render(EventService $eventService)
    {
        [$active, $completed] = $eventService->getPublicIndexCachedData([
            'sort' => $this->sort,
            'order' => $this->order,
            'projectId' => $this->projectId,
        ]);

        $events = $this->type === 'completed' ? $completed : $active;

        return view('livewire.front.events-index', [
            'events' => $events,
        ]);
    }
}
