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

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Chart\ProjectTranscriptionChart;
use Illuminate\Http\JsonResponse;

class ProjectDigitizationController extends Controller
{
    /**
     * A year of the project's Digitizations chart in a neutral shape: the days and each expedition's running totals.
     */
    public function __invoke(Project $project, int $year, ProjectTranscriptionChart $chart): JsonResponse
    {
        return response()->json($chart->year($project, $year));
    }
}
