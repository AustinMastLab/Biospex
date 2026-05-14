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

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class IndexController
 */
class IndexController extends ApiController
{
    public function index(): string
    {
        return \View::make('front.api-index');
    }

    /**
     * Reset OpCache
     */
    public function resetOpcache(Request $request): Response
    {
        if (function_exists('opcache_reset')) {
            if (opcache_reset()) {
                return $this->respondWithArray(['message' => 'OpCache reset successful']);
            }

            return $this->errorInternalError('OpCache reset failed');
        }

        return $this->errorInternalError('OpCache extension not loaded');
    }
}
