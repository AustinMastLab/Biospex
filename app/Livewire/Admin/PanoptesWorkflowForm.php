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

use App\Jobs\PanoptesProjectUpdateJob;
use App\Models\Expedition;
use App\Models\PanoptesProject;
use App\Services\Permission\CheckPermission;
use App\Traits\WithModalNotices;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The expedition's Zooniverse workflow id (Edit Workflow Id, in the global modal).
 */
class PanoptesWorkflowForm extends Component
{
    use WithModalNotices;

    #[Locked]
    public int $expeditionId;

    public string $panoptesWorkflowId = '';

    public function mount(Expedition $expedition): void
    {
        $expedition->loadMissing('panoptesProject');
        $this->expeditionId = $expedition->id;
        $this->panoptesWorkflowId = (string) ($expedition->panoptesProject->panoptes_workflow_id ?? '');
    }

    /**
     * Save the workflow id and update the Panoptes project from Zooniverse.
     */
    public function save(): void
    {
        $this->validate(['panoptesWorkflowId' => 'required|integer'], [], ['panoptesWorkflowId' => t('workflow id')]);

        $expedition = Expedition::with('project.group')->findOrFail($this->expeditionId);
        if (! CheckPermission::handle('updateProject', $expedition->project->group)) {
            $this->notice(t('You are not authorized for this action.'));

            return;
        }

        $panoptesProject = PanoptesProject::updateOrCreate(
            ['project_id' => $expedition->project_id, 'expedition_id' => $expedition->id],
            ['panoptes_workflow_id' => $this->panoptesWorkflowId],
        );
        PanoptesProjectUpdateJob::dispatch($panoptesProject);

        $this->finish(t('Workflow id is updated.'));
    }

    public function render(): View
    {
        return view('livewire.admin.panoptes-workflow-form');
    }
}
