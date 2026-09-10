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

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Project\ProjectService;
use App\Services\Transcriptions\StateCountyService;
use Illuminate\Http\RedirectResponse;
use JavaScript;
use Redirect;
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
        protected StateCountyService $stateCountyService) {}

    /**
     * Public Projects page.
     */
    public function index(): \Illuminate\Contracts\View\View
    {
        $projects = $this->projectService->getPublicIndex();

        return View::make('front.project.index', compact('projects'));
    }

    /**
     * Show public project page.
     */
    public function show($slug): RedirectResponse|\Illuminate\Contracts\View\View
    {
        $project = $this->projectService->getProjectPageBySlug($slug);

        if ($project === null) {
            return Redirect::route('front.projects.index')->with('danger', t('Unable to locate project. Please alert the Admin.'));
        }

        [$expeditions, $expeditionsCompleted] = $this->projectService->partitionExpeditions($project->expeditions);

        $years = ! isset($project->amChart) || is_null($project->amChart->data) ?
            null : array_keys($project->amChart->data);

        $states = $this->stateCountyService->getStateTranscriptCount($project->id);
        $max = abs(round(($states->max('value') + 500), -3));

        JavaScript::put([
            'max' => $max,
            'states' => $states->toJson(),
            'years' => $years,
            'project' => $project->id,
        ]);

        return View::make('front.project.home', compact('project', 'years', 'expeditions', 'expeditionsCompleted'));
    }
}
