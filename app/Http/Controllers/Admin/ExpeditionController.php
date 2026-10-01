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
use App\Http\Requests\ExpeditionFormRequest;
use App\Jobs\DeleteExpeditionJob;
use App\Models\Expedition;
use App\Models\Project;
use App\Services\Expedition\ExpeditionService;
use App\Services\JavascriptService;
use App\Services\Permission\CheckPermission;
use App\Services\Workflow\WorkflowService;
use Auth;
use Redirect;
use Throwable;
use View;

/**
 * Class ExpeditionController
 */
class ExpeditionController extends Controller
{
    /**
     * ExpeditionController constructor.
     */
    public function __construct(
        protected ExpeditionService $expeditionService,
        protected JavascriptService $javascriptService
    ) {}

    /**
     * Display all expeditions for user.
     */
    public function index(): mixed
    {
        return View::make('admin.expedition.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Project $project, WorkflowService $workflowService): mixed
    {
        $project->load('group');

        if (! CheckPermission::handle('createProject', $project->group)) {
            return Redirect::route('admin.projects.index');
        }

        $workflowOptions = $workflowService->getWorkflowSelect();

        $this->javascriptService->expeditionCreate($project);

        return View::make('admin.expedition.create', compact('project', 'workflowOptions'));
    }

    /**
     * Store new expedition.
     */
    public function store(Project $project, ExpeditionFormRequest $request): mixed
    {
        try {
            $project->load('group');

            if (! CheckPermission::handle('createProject', $project->group)) {
                return Redirect::route('admin.projects.index');
            }

            $expedition = $this->expeditionService->store($project, $request->all());

            return Redirect::route('admin.expeditions.show', [$expedition])->with('success', t('Record was created successfully.'));
        } catch (Throwable $throwable) {
            return Redirect::route('admin.projects.show', [$project])->with('danger', t('An error occurred when saving record. Please contact the administrator.'));
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Expedition $expedition): mixed
    {
        $expedition->load('project.group', 'downloads', 'stat');

        if (! CheckPermission::handle('readProject', $expedition->project->group)) {
            return Redirect::route('admin.projects.index');
        }

        $this->javascriptService->expeditionShow($expedition);

        return View::make('admin.expedition.show', compact('expedition'));
    }

    /**
     * Show the form for editing Expedition.
     */
    public function edit(Expedition $expedition, WorkflowService $workflowService): mixed
    {
        $expedition->load(['project.group', 'downloads', 'stat', 'workflowManager']);

        if (! CheckPermission::handle('updateProject', $expedition->project->group)) {
            return Redirect::route('admin.projects.index');
        }

        $workflowOptions = $workflowService->getWorkflowSelect();

        $subjectIds = $this->expeditionService->getSubjectIdsByExpeditionId($expedition)->toArray();

        $this->javascriptService->expeditionEdit($expedition, $subjectIds);

        return View::make('admin.expedition.edit', compact('expedition', 'workflowOptions'));
    }

    /**
     * Update expedition.
     */
    public function update(Expedition $expedition, ExpeditionFormRequest $request): mixed
    {
        $expedition->load('project.group');

        if (! CheckPermission::handle('updateProject', $expedition->project->group)) {
            return Redirect::route('admin.projects.index');
        }

        try {
            $this->expeditionService->update($expedition, $request->all());

            return Redirect::route('admin.expeditions.show', [$expedition])
                ->with('success', t('Record was updated successfully.'));
        } catch (Throwable $throwable) {
            return Redirect::route('admin.expeditions.edit', [$expedition])
                ->with('danger', t('An error occurred when saving record. Please contact the administrator.'));
        }
    }

    /**
     * Soft delete the specified resource from storage.
     */
    public function destroy(Expedition $expedition): mixed
    {
        $expedition->load('project.group', 'workflowManager', 'panoptesProject');

        if (! CheckPermission::handle('isOwner', $expedition->project->group)) {
            return Redirect::route('admin.projects.index');
        }

        try {
            if (isset($expedition->workflowManager) || isset($expedition->panoptesProject)) {

                return Redirect::route('admin.expeditions.show', [$expedition])
                    ->with('danger', t('An Expedition workflow or process exists and cannot be deleted. Even if the process has been stopped locally, other services may need to refer to the existing Expedition.'));
            }

            DeleteExpeditionJob::dispatch(Auth::user(), $expedition);

            return Redirect::route('admin.projects.show', [$expedition->project])
                ->with('success', t('Record has been scheduled for deletion and changes will take effect in a few minutes. You will receive an email when complete.'));
        } catch (Throwable $throwable) {
            return Redirect::route('admin.expeditions.show', [$expedition])->with('danger', t('An error occurred when deleting record.'));
        }
    }
}
