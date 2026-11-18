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

namespace App\Livewire\Admin;

use App\Models\Expedition;
use App\Models\Group;
use App\Traits\WithDataTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The Expeditions table on a group's admin page: every expedition in the group's projects.
 */
class GroupExpeditionsTable extends Component
{
    use WithDataTable;

    #[Locked]
    public int $groupId;

    public function mount(Group $group): void
    {
        $this->groupId = $group->id;
        $this->sortField = 'title';
    }

    public function pageName(): string
    {
        return 'expeditionsPage';
    }

    protected function sortableFields(): array
    {
        return ['title', 'completed'];
    }

    protected function rows(): LengthAwarePaginator
    {
        $query = Expedition::query()
            ->select('id', 'uuid', 'project_id', 'title', 'completed')
            ->whereHas('project', fn ($query) => $query->where('group_id', $this->groupId));

        $term = $this->searchTerm();
        if ($term !== '') {
            $query->where('title', 'like', "%{$term}%");
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('id')
            ->paginate($this->perPage, ['*'], $this->pageName());
    }

    public function render(): View
    {
        return view('livewire.admin.group-expeditions-table', ['rows' => $this->rows()]);
    }
}
