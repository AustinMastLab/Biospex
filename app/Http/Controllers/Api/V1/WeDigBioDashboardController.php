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

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\WeDigBioDashboard;
use App\Http\Resources\WeDigBioDashboardCollection;
use App\Services\Dashboard\WeDigBioDashboardProcess;
use Illuminate\Http\Response;

/**
 * Class WeDigBioDashboardController
 */
class WeDigBioDashboardController extends ApiController
{
    /**
     * WeDigBioDashboardController constructor.
     */
    public function __construct(protected WeDigBioDashboardProcess $weDigBioDashboardProcess) {}

    /**
     * PusherTranscription List.
     *
     * Show JSON representation of PusherTranscription items.
     *
     * @Get("/{?start,rows,date_start,date_end,project_uuid,expedition_uuid}")
     *
     * @Versions({"v1"})
     *
     * @Parameters({
     *
     *     @Parameter("start", description="The start of the results to view.", default=0),
     *     @Parameter("date_start", description="Return results >= to UTC timestamp."),
     *     @Parameter("date_end", description="Return results <= to UTC timestamp."),
     *     @Parameter("project_uuid", description="Biospex Project ID resource belongs to."),
     *     @Parameter("expedition_uuid", description="Biospex Expedition ID resource belongs to.")
     * })
     */
    public function index(): Response|WeDigBioDashboardCollection
    {
        $request = \Request::all();
        $this->weDigBioDashboardProcess->setDashboardQuery($request);
        $numFound = $this->weDigBioDashboardProcess->getTotalCount();
        $limit = (int) config('config.wedigbio_dashboard_rows', 100);
        $offset = $this->weDigBioDashboardProcess->setOffset($request);

        $items = $this->weDigBioDashboardProcess->getItems($limit, $offset);

        return (new WeDigBioDashboardCollection($items))->additional([
            'numFound' => $numFound,
            'start' => $offset,
            'rows' => $limit,
        ])->collectionRoute('api.v1.wedigbio-dashboard.index')
            ->resourceRoute('api.v1.wedigbio-dashboard.show');
    }

    /**
     * Store resource.
     */
    public function store(): Response
    {
        return $this->errorForbidden();
    }

    /**
     * PusherTranscription List.
     *
     * Show JSON representation of PusherTranscription items.
     *
     * @Get("/{guid}")
     *
     * @Versions({"v1"})
     *
     * @Parameters({
     *
     *     @Parameter("guid", description="GUID of specific resource item", required=true)
     * })
     */
    public function show(string $guid): Response|WeDigBioDashboard
    {
        $result = $this->weDigBioDashboardProcess->showApiDashboard($guid);

        return (new WeDigBioDashboard($result))->resourceRoute('api.v1.wedigbio-dashboard.show');
    }

    /**
     * Update resource.
     */
    public function update(): Response
    {
        return $this->errorForbidden();
    }

    /**
     * Destroy resource.
     */
    public function destroy(): Response
    {
        return $this->errorForbidden();
    }
}
