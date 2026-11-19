<?php

/*
 * Copyright (C) 2014 - 2025, Biospex
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

namespace App\Http\Controllers\Admin;

use App\Models\Expedition;
use App\Services\Expedition\ExpeditionService;
use App\Services\Permission\CheckPermission;
use App\Services\Workflow\WorkflowService;
use Redirect;
use View;

class ExpeditionCloneController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ExpeditionService $expeditionService,
        protected WorkflowService $workflowService
    ) {}

    public function __invoke(Expedition $expedition): mixed
    {
        $expedition->load(['project.group', 'downloads', 'stat']);

        if (! CheckPermission::handle('createProject', $expedition->project->group)) {
            return Redirect::route('admin.projects.index');
        }

        $workflowOptions = $this->workflowService->getWorkflowSelect();

        return View::make('admin.expedition.clone', compact('expedition', 'workflowOptions'));
    }
}
