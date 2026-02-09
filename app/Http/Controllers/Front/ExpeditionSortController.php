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
use App\Services\Expedition\ExpeditionService;
use Request;
use Response;
use View;

class ExpeditionSortController extends Controller
{
    /**
     * Displays Completed Expeditions on public page.
     */
    public function __invoke(ExpeditionService $expeditionService): mixed
    {
        if (! Request::ajax()) {
            return Response::json(['message' => t('Request must be ajax.')], 400);
        }

        $result = $expeditionService->getPublicSortedExpeditionsWithMeta(Request::all());

        return Response::make(
            View::make('front.expedition.partials.expedition', ['expeditions' => $result['expeditions']])
        )->header('X-Biospex-Cache', $result['cache_status']);
    }
}
