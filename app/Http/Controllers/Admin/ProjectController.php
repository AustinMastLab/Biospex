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
use App\Http\Requests\ProjectFormRequest;
use App\Jobs\DeleteProjectJob;
use App\Models\Project;
use App\Services\Group\GroupService;
use App\Services\Permission\CheckPermission;
use App\Services\Project\ProjectService;
use Auth;
use Illuminate\Http\RedirectResponse;
use Redirect;
use Request;
use Throwable;
use View;

/**
 * Class ProjectController
 */
class ProjectController extends Controller
{
    /**
     * ProjectController constructor.
     */
    public function __construct(
        protected ProjectService $projectService,
        protected GroupService $groupService,
    ) {}

    /**
     * Show projects list for admin page.
     */
    public function index(): \Illuminate\View\View
    {
        $groups = $this->groupService->getUserGroupCount(Auth::id());

        return $groups === 0 ? View::make('admin.welcome') : View::make('admin.project.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): \Illuminate\View\View
    {
        $groupOptions = $this->groupService->getUsersGroupsSelect(Request::user());

        $vars = compact('groupOptions');

        return View::make('admin.project.create', $vars);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): \Illuminate\Contracts\View\View|RedirectResponse
    {
        $viewParams = $this->projectService->getProjectShow($project);

        if (! CheckPermission::handle('readProject', $project->group)) {
            return Redirect::route('admin.projects.index');
        }

        return View::make('admin.project.show', $viewParams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectFormRequest $request): mixed
    {
        $group = $this->groupService->group->find($request->get('group_id'));

        if (! CheckPermission::handle('createProject', $group)) {
            return Redirect::route('admin.projects.index');
        }

        $project = $this->projectService->create($request->all());

        if ($project) {
            return Redirect::route('admin.projects.show', [$project])
                ->with('success', t('Record was created successfully.'));
        }

        return Redirect::route('admin.projects.create')->withInput()->with('danger', t('An error occurred when saving record.'));
    }

    /**
     * Edit project.
     */
    public function edit(Project $project): \Illuminate\View\View
    {
        $project->load(['group', 'assets']);
        $groupOptions = $this->groupService->getUsersGroupsSelect(Request::user());
        $resources = $project->assets;
        $vars = compact('project', 'resources', 'groupOptions');

        return View::make('admin.project.edit', $vars);
    }

    /**
     * Update project.
     */
    public function update(Project $project, ProjectFormRequest $request): RedirectResponse
    {
        $project->load('group');

        if (! CheckPermission::handle('updateProject', $project->group)) {
            return Redirect::route('admin.projects.index');
        }

        $result = $this->projectService->update($request->all(), $project);

        return $result ?
            Redirect::route('admin.projects.show', $project)->with('success', t('Record was updated successfully.')) :
            Redirect::route('admin.projects.index')->with('danger', t('Error while updating record.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Project $project): RedirectResponse
    {
        $this->projectService->loadRelationsForDelete($project);

        if (! CheckPermission::handle('isOwner', $project->group)) {
            return Redirect::route('admin.projects.index');
        }

        try {
            if ($project->panoptesProjects->isNotEmpty() || $project->workflowManagers->isNotEmpty()) {

                Redirect::route('admin.projects.index')
                    ->with('danger', t('An Expedition workflow or process exists and cannot be deleted. Even if the process has been stopped locally, other services may need to refer to the existing Expedition.'));
            }

            DeleteProjectJob::dispatch($project);

            return Redirect::route('admin.projects.index')
                ->with('success', t('Record has been scheduled for deletion and changes will take effect in a few minutes.'));
        } catch (Throwable $throwable) {

            return Redirect::route('admin.projects.index')->with('danger', t('An error occurred when deleting record.'));
        }
    }
}
