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
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectsIndex extends Component
{
    public string $sort = 'date';

    public string $order = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->order = 'asc';
        }
    }

    public function render(ProjectService $projectService)
    {
        $projects = $projectService->getAdminIndex(Auth::user(), [
            'sort' => $this->sort,
            'order' => $this->order,
        ]);

        return view('livewire.admin.projects-index', [
            'projects' => $projects,
        ]);
    }
}
