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
use App\Services\Actor\GeoLocate\GeoLocateStatService;
use App\Services\Permission\CheckPermission;
use App\Traits\WithModalNotices;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * The expedition's GeoLocate community and data source (GeoLocate Community & Data Source Form, in the global modal).
 *
 * Choosing "New" (an empty community id) asks for the new community's name.
 */
class GeoLocateCommunityForm extends Component
{
    use WithModalNotices;

    #[Locked]
    public int $expeditionId;

    /**
     * The chosen community's id, or '' for a new community.
     */
    public string $communityId = '';

    public string $community = '';

    public string $dataSource = '';

    public function mount(Expedition $expedition): void
    {
        $expedition->loadMissing('geoLocateDataSource');
        $this->expeditionId = $expedition->id;
        $this->communityId = (string) ($expedition->geoLocateDataSource->geo_locate_community_id ?? '');
        $this->dataSource = (string) ($expedition->geoLocateDataSource->data_source ?? '');
    }

    /**
     * Save the community (creating it when new) and the data source, checking both with GeoLocate.
     */
    public function save(GeoLocateStatService $geoLocateStatService): void
    {
        $expedition = $this->expedition();

        $this->validate([
            'communityId' => ['nullable', Rule::in($expedition->project->geoLocateCommunities->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'community' => [Rule::requiredIf($this->communityId === ''), 'nullable', 'string', 'max:255'],
            'dataSource' => 'required|string|max:255',
        ], [
            'community.required' => t('Enter a name for the new community.'),
        ], [
            'communityId' => t('community'),
            'community' => t('new community'),
            'dataSource' => t('data source'),
        ]);

        if (! CheckPermission::handle('updateProject', $expedition->project->group)) {
            $this->notice(t('You are not authorized for this action.'));

            return;
        }

        try {
            $geoLocateStatService->saveCommunityDataSource([
                'community_id' => $this->communityId === '' ? null : (int) $this->communityId,
                'community' => $this->communityId === '' ? $this->community : null,
                'data_source' => $this->dataSource,
            ], $expedition);
        } catch (Throwable $throwable) {
            $this->notice($throwable->getMessage());

            return;
        }

        $this->finish(t('Community and data source added.'));
    }

    private function expedition(): Expedition
    {
        return Expedition::with('project.group', 'project.geoLocateCommunities')->findOrFail($this->expeditionId);
    }

    public function render(): View
    {
        return view('livewire.admin.geo-locate-community-form', [
            'communities' => $this->expedition()->project->geoLocateCommunities,
        ]);
    }
}
