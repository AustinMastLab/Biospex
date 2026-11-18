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

use App\Models\Event;
use App\Traits\WithDataTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The Teams Summary on an event's admin page: one row per team member with their digitizations in the event.
 */
class EventTeamsTable extends Component
{
    use WithDataTable;

    #[Locked]
    public int $eventId;

    public function mount(Event $event): void
    {
        $this->eventId = $event->id;
        $this->sortField = 'team';
    }

    public function pageName(): string
    {
        return 'teamsPage';
    }

    protected function sortableFields(): array
    {
        return ['team', 'user', 'transcriptions_count'];
    }

    /**
     * Team members of the event, with the number of digitizations each made during it.
     */
    protected function rows(): LengthAwarePaginator
    {
        $transcriptions = DB::table('event_transcriptions')
            ->selectRaw('count(*)')
            ->whereColumn('event_transcriptions.user_id', 'event_users.id')
            ->where('event_transcriptions.event_id', $this->eventId);

        $query = DB::table('event_team_user')
            ->join('event_teams', 'event_teams.id', '=', 'event_team_user.team_id')
            ->join('event_users', 'event_users.id', '=', 'event_team_user.user_id')
            ->where('event_teams.event_id', $this->eventId)
            ->select('event_teams.title as team', 'event_users.nfn_user as user')
            ->selectSub($transcriptions, 'transcriptions_count');

        $term = $this->searchTerm();
        if ($term !== '') {
            $query->where(function ($query) use ($term) {
                $query->where('event_teams.title', 'like', "%{$term}%")
                    ->orWhere('event_users.nfn_user', 'like', "%{$term}%");
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('user')
            ->paginate($this->perPage, ['*'], $this->pageName());
    }

    public function render(): View
    {
        return view('livewire.admin.event-teams-table', ['rows' => $this->rows()]);
    }
}
