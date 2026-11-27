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
use App\Services\Permission\CheckPermission;
use App\Services\Reconcile\ReconcileUserFormService;
use App\Traits\WithModalNotices;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Upload Reconciled With User Opinion: a user-reviewed CSV of the expedition's reconciled file (in the global modal).
 */
class ReconcileUserUpload extends Component
{
    use WithFileUploads;
    use WithModalNotices;

    #[Locked]
    public int $expeditionId;

    public ?TemporaryUploadedFile $file = null;

    public function mount(Expedition $expedition): void
    {
        $this->expeditionId = $expedition->id;
    }

    /**
     * Store the CSV; it is then listed in the expedition's downloads.
     */
    public function save(ReconcileUserFormService $reconcileUserFormService): void
    {
        $this->validate(
            ['file' => 'required|file|extensions:csv'],
            ['file.extensions' => t('File must be a CSV.')],
            ['file' => t('file')],
        );

        $expedition = Expedition::with('project.group')->findOrFail($this->expeditionId);
        if (! CheckPermission::handle('updateProject', $expedition->project->group)) {
            $this->notice(t('You are not authorized for this action.'));

            return;
        }

        if (! $reconcileUserFormService->reconciledWithUserFile($expedition, $this->file->getRealPath())) {
            $this->notice(t('Error uploading file. Please try again or contact the Administration.'));

            return;
        }

        $this->finish(t('File upload was successful. It will now be listed in your downloads section of the Expedition.'));
    }

    public function render(): View
    {
        return view('livewire.admin.reconcile-user-upload');
    }
}
