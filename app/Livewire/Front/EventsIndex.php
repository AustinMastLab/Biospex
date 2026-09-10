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
use App\Traits\WithIncrementalIndex;
use Illuminate\Pagination\Paginator;
use Livewire\Component;

class EventsIndex extends Component
{
    use WithIncrementalIndex;

    protected EventService $eventService;

    public function boot(EventService $eventService): void
    {
        $this->eventService = $eventService;
    }

    protected function sortableFields(): array
    {
        return $this->projectId === null
            ? ['title', 'project', 'date']
            : ['title', 'date'];
    }

    protected function typeChanged(): void
    {
        $this->dispatch('event-type-changed', type: $this->type);
    }

    protected function getPage(): Paginator
    {
        return $this->eventService->getPublicIndexPage([
            'type' => $this->type,
            'sort' => $this->sort,
            'order' => $this->order,
            'projectId' => $this->projectId,
        ], $this->page);
    }

    public function render()
    {
        return view('livewire.front.events-index');
    }
}
