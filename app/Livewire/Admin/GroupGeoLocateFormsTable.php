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

use App\Models\GeoLocateForm;
use App\Models\Group;
use App\Traits\WithDataTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The GeoLocateExport Forms table on a group's admin page, with the expeditions each form is assigned to.
 */
class GroupGeoLocateFormsTable extends Component
{
    use WithDataTable;

    #[Locked]
    public int $groupId;

    #[Locked]
    public string $groupUuid;

    public function mount(Group $group): void
    {
        $this->groupId = $group->id;
        $this->groupUuid = $group->uuid;
        $this->sortField = 'name';
    }

    public function pageName(): string
    {
        return 'formsPage';
    }

    protected function sortableFields(): array
    {
        return ['name', 'expeditions_count'];
    }

    protected function rows(): LengthAwarePaginator
    {
        $query = GeoLocateForm::query()
            ->where('group_id', $this->groupId)
            ->withCount('expeditions')
            ->with('expeditions:expeditions.id,expeditions.uuid');

        $term = $this->searchTerm();
        if ($term !== '') {
            $query->where('name', 'like', "%{$term}%");
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('id')
            ->paginate($this->perPage, ['*'], $this->pageName());
    }

    public function render(): View
    {
        return view('livewire.admin.group-geo-locate-forms-table', ['rows' => $this->rows()]);
    }
}
