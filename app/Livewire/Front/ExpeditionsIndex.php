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

use App\Services\Expedition\ExpeditionService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Component;

class ExpeditionsIndex extends Component
{
    public string $type = 'active';

    public string $sort = 'date';

    public string $order = 'asc';

    public ?int $projectId = null;

    public int $page = 1;

    public bool $hasMore = false;

    public Collection $expeditions;

    protected ExpeditionService $expeditionService;

    public function boot(ExpeditionService $expeditionService): void
    {
        $this->expeditionService = $expeditionService;
    }

    public function mount(?string $type = null, ?int $projectId = null): void
    {
        if ($type !== null) {
            $this->type = in_array($type, ['active', 'completed'], true) ? $type : 'active';
        }

        if ($projectId !== null) {
            $this->projectId = $projectId;
        }

        $this->resetExpeditions();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['title', 'date'], true)) {
            return;
        }

        if ($this->sort === $field) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->order = 'asc';
        }

        $this->resetExpeditions();
    }

    public function setType(string $type): void
    {
        $this->type = in_array($type, ['active', 'completed'], true) ? $type : 'active';

        $this->resetExpeditions();
        $this->dispatch('expedition-type-changed', type: $this->type);
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }

        $this->page++;
        $expeditions = $this->getExpeditionPage();

        $this->expeditions = $this->expeditions->concat($expeditions->items());
        $this->hasMore = $expeditions->hasMorePages();
    }

    public function render()
    {
        return view('livewire.front.expeditions-index');
    }

    protected function resetExpeditions(): void
    {
        $this->page = 1;
        $expeditions = $this->getExpeditionPage();

        $this->expeditions = collect($expeditions->items());
        $this->hasMore = $expeditions->hasMorePages();
    }

    protected function getExpeditionPage(): Paginator
    {
        return $this->expeditionService->getPublicIndexPage([
            'type' => $this->type,
            'sort' => $this->sort,
            'order' => $this->order,
            'projectId' => $this->projectId,
        ], $this->page);
    }
}
