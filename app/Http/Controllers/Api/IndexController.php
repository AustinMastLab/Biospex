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
    /**
     * @return string
     */
    public function index()
    {
        return \View::make('front.api-index');
    }

    /**
     * Reset OpCache
     *
     * @return Response
     */
    public function resetOpcache(Request $request)
    {
        $token = $request->input('token');
        $validToken = config('app.opcache_webhook_token', null);

        if (empty($validToken) || $token !== $validToken) {
            return $this->errorUnauthorized('Invalid reset token.');
        }

        if (function_exists('opcache_reset')) {
            if (opcache_reset()) {
                return $this->respondWithArray(['message' => 'OpCache reset successful']);
            }

            return $this->errorInternalError('OpCache reset failed');
        }

        return $this->errorInternalError('OpCache extension not loaded');
    }
}
