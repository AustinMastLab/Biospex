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
use App\Services\Chart\ProjectTranscriptionChart;
use App\Services\Project\ProjectService;
use App\Services\Transcriptions\StateCountyService;
use Illuminate\Http\RedirectResponse;
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
        protected StateCountyService $stateCountyService,
        protected ProjectTranscriptionChart $projectTranscriptionChart) {}

    /**
     * Public Projects page.
     */
    public function index(): \Illuminate\Contracts\View\View
    {
        return View::make('front.project.index');
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

        // The Digitizations chart's years; no years means no charts (Digitizations and the heat map).
        $years = $this->projectTranscriptionChart->years($project) ?: null;
        $states = $years === null ? collect() : $this->stateCountyService->getStateTranscriptCount($project->id);

        return View::make('front.project.home', compact('project', 'years', 'states'));
    }
}
