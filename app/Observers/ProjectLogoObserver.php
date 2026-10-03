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

namespace App\Observers;

use App\Models\Project;
use App\Services\Asset\ImageUploadService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Removes a project's logo from storage once the project is deleted.
 */
class ProjectLogoObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(protected ImageUploadService $imageUploadService) {}

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        if (empty($project->logo_path)) {
            return;
        }

        $this->imageUploadService->deleteImage($project->logo_path, 'Project');
    }
}
