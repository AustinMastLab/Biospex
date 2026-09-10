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
use App\Traits\WithIncrementalExpeditions;
use Illuminate\Pagination\Paginator;
use Livewire\Component;

class ExpeditionsIndex extends Component
{
    use WithIncrementalExpeditions;

    protected ExpeditionService $expeditionService;

    public function boot(ExpeditionService $expeditionService): void
    {
        $this->expeditionService = $expeditionService;
    }

    protected function sortableExpeditionFields(): array
    {
        return ['title', 'date'];
    }

    protected function typeChanged(): void
    {
        $this->dispatch('expedition-type-changed', type: $this->type);
    }

    public function render()
    {
        return view('livewire.front.expeditions-index');
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
