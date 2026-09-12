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

use App\Services\Project\ProjectService;
use App\Traits\WithIncrementalIndex;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectsIndex extends Component
{
    use WithIncrementalIndex;

    protected function sortableFields(): array
    {
        return ['title', 'group', 'date'];
    }

    protected function getPage(): Paginator
    {
        return app(ProjectService::class)->getAdminIndexPage(Auth::user(), [
            'sort' => $this->sort,
            'order' => $this->order,
        ], $this->page);
    }

    public function hydrateRecords(): void
    {
        $this->records = app(ProjectService::class)->getAdminIndexRecords(
            Auth::user(),
            $this->records->pluck('id')->all(),
        );
    }

    public function render()
    {
        return view('livewire.admin.projects-index');
    }
}
