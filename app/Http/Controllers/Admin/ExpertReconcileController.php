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
use App\Models\Expedition;
use App\Services\Api\PanoptesApiService;
use App\Services\Api\ZooniverseTalkApiService;
use App\Services\Permission\CheckPermission;
use App\Services\Reconcile\ExpertReconcileService;
use App\Services\Reconcile\ReconcileService;
use App\Traits\SkipZooniverse;
use Redirect;
use Request;
use View;

/**
 * Class ExpertReconcileController
 */
class ExpertReconcileController extends Controller
{
    use SkipZooniverse;

    /**
     * ExpertReconcileController constructor.
     */
    public function __construct(
        protected ExpertReconcileService $expertReconcileService,
        protected PanoptesApiService $panoptesApiService,
        protected ZooniverseTalkApiService $zooniverseTalkApiService
    ) {}

    /**
     * Show files needing reconciliation with pagination.
     */
    public function index(Expedition $expedition)
    {
        $expedition->load(['project.group.owner', 'panoptesProject']);

        if (! CheckPermission::handle('updateProject', $expedition->project->group)) {
            return Redirect::route('admin.expeditions.show', [$expedition->project_id, $expedition->id]);
        }

        $reconciles = $this->expertReconcileService->getPagination((int) $expedition->id);

        if ($reconciles->isEmpty()) {

            return Redirect::route('admin.expeditions.show', [$expedition->project_id, $expedition->id])
                ->with('danger', t('Reconcile data for processing is missing.'));
        }

        $comments = $this->zooniverseTalkApiService->getComments($expedition->panoptesProject->panoptes_project_id, $reconciles->first()->subject_id);

        $location = $this->panoptesApiService->getSubjectImageLocation($reconciles->first()->subject_id);
        $imgUrl = $this->expertReconcileService->getImageUrl($reconciles->first()->subject_imageName, $location);
        $columns = explode('|', $reconciles->first()->subject_columns);

        return View::make('admin.reconcile.index', compact('reconciles', 'columns', 'imgUrl', 'expedition', 'comments'));
    }

    /**
     * Start Expert Review by sending the expedition to the reconcile trigger queue with
     * explanations, which runs the BiospexReconcile312 Lambda, and redirect to index for processing.
     *
     * @throws \Throwable
     */
    public function create(Expedition $expedition, ReconcileService $reconcileLambdaService)
    {
        $expedition->load('project.group.owner');

        if (! CheckPermission::handle('updateProject', $expedition->project->group)) {
            return Redirect::route('admin.expeditions.show', [$expedition->project_id, $expedition->id]);
        }

        if ($this->skipReconcile($expedition->id)) {
            return Redirect::route('admin.expeditions.show', [$expedition])
                ->with('warning', t('Expert Review Process for Expedition (:id) was skipped. Please contact Biospex Administration', [':id' => $expedition->id]));
        }

        $reconcileLambdaService->sendToReconcileTriggerQueue($expedition->id, true);

        return Redirect::route('admin.expeditions.show', [$expedition])
            ->with('success', t('The job to create the Expert Review has been submitted. You will receive an email when it is complete and review can begin.'));
    }

    /**
     * Update reconciled record.
     */
    public function update(Expedition $expedition)
    {
        if (! $this->expertReconcileService->updateRecord(Request::all())) {

            return Redirect::back()->with('danger', t('Error while updating record.'));
        }

        return Redirect::to(Request::get('page'))->with('success', t('Record was updated successfully.'));
    }
}
