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

use App\Facades\CountHelper;
use App\Models\Project;
use App\Traits\WithDataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The Participant Summary on a project's statistics page: each participant's expeditions, digitizations and last date.
 *
 * The counts come from the cached MongoDB aggregation the page's chart also uses; only the visible page is rendered.
 */
class ProjectParticipantsTable extends Component
{
    use WithDataTable;

    #[Locked]
    public int $projectId;

    public function mount(Project $project): void
    {
        $this->projectId = $project->id;
        $this->sortField = 'transcriptionCount';
        $this->sortDirection = 'desc';
    }

    public function pageName(): string
    {
        return 'participantsPage';
    }

    protected function sortableFields(): array
    {
        return ['user_name', 'expeditionCount', 'transcriptionCount', 'last_date'];
    }

    protected function rows(): LengthAwarePaginator
    {
        // Dates stay timestamps for sorting; only the visible page is formatted.
        $participants = CountHelper::getTranscribersTranscriptionCount($this->projectId);

        $timezone = Auth::user()->profile->timezone;

        return $this->paginateCollection($participants, ['user_name'])
            ->through(fn (array $participant) => [
                ...$participant,
                'last_date' => $participant['last_date'] > 0
                    ? Carbon::createFromTimestamp($participant['last_date'])->setTimezone($timezone)->format('Y-m-d')
                    : '',
            ]);
    }

    public function render(): View
    {
        return view('livewire.admin.project-participants-table', ['rows' => $this->rows()]);
    }
}
