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

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ExpeditionCollection;
use App\Http\Resources\ExpeditionResource;
use App\Services\Expedition\ExpeditionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Read-only API for public expeditions (those running on Zooniverse).
 */
class ExpeditionController extends ApiController
{
    public function __construct(protected ExpeditionService $expeditionService) {}

    /**
     * List public expeditions.
     *
     * Query: `project_uuid`, `status` (active|completed), `per_page` (1-100, default 25), `page`.
     */
    public function index(Request $request): ExpeditionCollection
    {
        $page = $this->expeditionService->getApiPage(
            $request->only(['project_uuid', 'status']),
            $this->perPage($request),
        );

        return new ExpeditionCollection($page);
    }

    /**
     * Show one public expedition by UUID.
     */
    public function show(string $uuid): Response|ExpeditionResource
    {
        $expedition = $this->expeditionService->findPublicByUuid($uuid);

        if ($expedition === null) {
            return $this->errorNotFound();
        }

        return new ExpeditionResource($expedition);
    }
}
