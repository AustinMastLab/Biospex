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
use App\Services\Actor\GeoLocate\GeoLocateExportService;
use App\Services\Actor\GeoLocate\GeoLocateFormService;
use App\Services\Actor\GeoLocate\GeoLocateStatService;
use App\Services\Permission\CheckPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Redirect;
use Response;
use Throwable;

/**
 * Class GeoLocateController
 *
 * This controller handles the actions related to GeoLocate form operations,
 * including storing and deleting GeoLocate form data and associated resources.
 */
class GeoLocateController extends Controller
{
    /**
     * Constructor method.
     *
     * @param  GeoLocateFormService  $geoLocateFormService  Service for handling form-related geo-location operations.
     * @param  GeoLocateStatService  $geoLocateStatService  Service for handling statistical geo-location operations.
     * @param  GeoLocateExportService  $geoLocateExportService  Service for handling export-related geo-location operations.
     * @return void
     */
    public function __construct(
        protected GeoLocateFormService $geoLocateFormService,
        protected GeoLocateStatService $geoLocateStatService,
        protected GeoLocateExportService $geoLocateExportService
    ) {}

    /**
     * Deletes the GeoLocate export form data and associated file for the given expedition.
     *
     * @param  Expedition  $expedition  The expedition instance for which the GeoLocate export data is being deleted.
     * @return JsonResponse|RedirectResponse A response indicating the result of the operation. On success, redirects with a success message. On failure, redirects with an error message.
     */
    public function destroy(Expedition $expedition): JsonResponse|RedirectResponse
    {
        try {
            $expedition->load('project.group', 'geoLocateDataSource');

            if (! CheckPermission::handle('isOwner', $expedition->project->group)) {
                return Redirect::route('admin.expeditions.show', [$expedition]);
            }

            $this->geoLocateExportService->destroyGeoLocate($expedition);

            return Redirect::route('admin.expeditions.show', [$expedition])
                ->with('success', t('GeoLocateExport form data and file deleted.'));
        } catch (Throwable $throwable) {
            return Redirect::route('admin.expeditions.show', [$expedition])
                ->with('danger', t('Error %s.', $throwable->getMessage()));
        }
    }
}
