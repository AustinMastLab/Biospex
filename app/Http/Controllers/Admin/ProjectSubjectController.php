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

use App\Http\Controllers\Controller;
use App\Jobs\DeleteUnassignedSubjectsJob;
use App\Models\Project;
use App\Services\Permission\CheckPermission;
use App\Services\Project\ProjectService;
use Auth;
use Illuminate\Http\RedirectResponse;
use Redirect;
use Throwable;
use View;

class ProjectSubjectController extends Controller
{
    /**
     * ProjectSubjectController constructor.
     */
    public function __construct(
        protected ProjectService $projectService,
    ) {}

    /**
     * Display project explore page.
     */
    public function index(Project $project): \Illuminate\View\View|RedirectResponse
    {
        $project->load(['group', 'expeditionStats']);

        if (! CheckPermission::handle('readProject', $project->group)) {
            return Redirect::route('admin.projects.index');
        }

        $subjectAssignedCount = $project->expeditionStats->sum('local_subject_count');

        return View::make('admin.project.explore', compact('project', 'subjectAssignedCount'));
    }

    /**
     * Delete all unassigned subjects for project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        try {
            DeleteUnassignedSubjectsJob::dispatch(Auth::user(), $project);

            return Redirect::route('admin.project-subjects.index', [$project])
                ->with('success', t('Subjects have been set for deletion. You will be notified by email when complete.'));
        } catch (Throwable $throwable) {
            return Redirect::route('admin.project-subjects.index', [$project])
                ->with('danger', t('An error occurred when deleting Subjects.'));
        }
    }
}
