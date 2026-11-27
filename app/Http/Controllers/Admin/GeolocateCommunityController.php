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
use App\Models\GeoLocateCommunity;
use App\Services\Actor\GeoLocate\GeoLocateFormService;
use App\Services\Actor\GeoLocate\GeoLocateStatService;
use App\Services\Helpers\GeneralService;
use App\Services\Permission\CheckPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Redirect;
use Request;
use Response;
use Throwable;
use View;

/**
 * Class GeolocateCommunityController
 *
 * This controller handles the display and updating of community
 * and datasource forms related to expeditions. It ensures proper
 * permissions and request handling for AJAX calls.
 */
class GeolocateCommunityController extends Controller
{
    /**
     * Constructor method for initializing services.
     *
     * @param  GeoLocateFormService  $geoLocateFormService  The service responsible for handling geolocation form-related operations.
     * @param  GeoLocateStatService  $geoLocateStatService  The service responsible for handling geolocation statistics operations.
     * @param  GeneralService  $generalService  The general service used for common functionalities.
     * @return void
     */
    public function __construct(
        protected GeoLocateFormService $geoLocateFormService,
        protected GeoLocateStatService $geoLocateStatService,
        protected GeneralService $generalService) {}

    /**
     * Handles the editing process for an expedition.
     *
     * @param  Expedition  $expedition  The expedition instance to be edited, including related loaded data and permission checks.
     * @return \Illuminate\View\View|JsonResponse|RedirectResponse
     *                                                             Returns a view containing the expedition edit form if the request is valid and permissions are granted.
     *                                                             Returns a JSON response with an error message if the request is not made via AJAX.
     *                                                             Redirects to the expedition details page if the user lacks proper permissions.
     */
    public function edit(Expedition $expedition): \Illuminate\View\View|JsonResponse|RedirectResponse
    {
        if (! Request::ajax()) {
            return Response::json(['message' => t('Request must be ajax.')], 400);
        }

        $expedition->load('project.group', 'project.geoLocateCommunities.geoLocateDataSources', 'geoLocateDataSource');

        if (! CheckPermission::handle('isOwner', $expedition->project->group)) {
            return Redirect::route('admin.expeditions.show', [$expedition]);
        }

        $communities = $expedition->project->geoLocateCommunities;

        return View::make('admin.geolocate.partials.community-form-body', compact('expedition', 'communities'));
    }

    /**
     * Deletes the specified community if conditions are met.
     *
     * @param  GeoLocateCommunity  $community  The community model instance to be deleted.
     * @return RedirectResponse A redirect response with a success or error message.
     */
    public function destroy(GeoLocateCommunity $community)
    {
        try {
            $community->load('project.group', 'geoLocateDataSources');

            if (! CheckPermission::handle('updateProject', $community->project->group)) {
                return Redirect::route('admin.projects.show', [$community->project])
                    ->with('danger', t('You are not authorized for this action.'));
            }

            if ($community->geoLocateDataSources->count() > 0) {
                return Redirect::route('admin.projects.show', [$community->project])
                    ->with('danger', t('You cannot delete a community that has data sources attached.'));
            }

            $community->delete();

            return Redirect::route('admin.projects.show', [$community->project])
                ->with('success', t('Record was deleted successfully.'));
        } catch (Throwable $throwable) {
            return Redirect::route('admin.projects.show', [$community->project])
                ->with('danger', t('Error %s.', $throwable->getMessage()));
        }
    }
}
