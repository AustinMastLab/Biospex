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
use Illuminate\Routing\Controller;

/**
 * Class ApiController
 */
class ApiController extends Controller
{
    protected int $statusCode = 200;

    const CODE_WRONG_ARGS = 'GEN-WRONG-ARGUMENTS';

    const CODE_NOT_FOUND = 'GEN-NOT-FOUND';

    const CODE_INTERNAL_ERROR = 'GEN-INTERNAL_ERROR';

    const CODE_UNAUTHORIZED = 'GEN-UNAUTHORIZED';

    const CODE_FORBIDDEN = 'GEN-FORBIDDEN';

    const CODE_INVALID_MIME_TYPE = 'GEN-INVALID-MIME-TYPE';

    /**
     * Getter for statusCode
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Setter for statusCode
     *
     * @param  int  $statusCode  Value to set
     * @return self
     */
    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Requested page size from `per_page`, kept between 1 and 100 (default 25).
     */
    protected function perPage(Request $request): int
    {
        return max(1, min(100, $request->integer('per_page', 25)));
    }

    /**
     * Send response created.
     */
    protected function respondWithCreated(): Response
    {
        return response()->noContent(201);
    }

    /**
     * Respond with array.
     */
    protected function respondWithArray(array $array, array $headers = []): Response
    {
        $content = json_encode($array);
        $headers = array_merge($headers, [
            'Content-Type' => 'application/json',
        ]);

        return response($content, $this->statusCode, $headers);
    }

    /**
     * Respond with error.
     */
    protected function respondWithError($message, $errorCode): Response
    {
        if ($this->statusCode === 200) {
            trigger_error(
                'You better have a really good reason for a 200 error...',
                E_USER_WARNING
            );
        }

        return $this->respondWithArray([
            'error' => [
                'code' => $errorCode,
                'http_code' => $this->statusCode,
                'message' => $message,
            ],
        ]);
    }

    /**
     * Generates a Response with a 403 HTTP header and a given message.
     */
    public function errorForbidden(string $message = 'Forbidden'): Response
    {
        return $this->setStatusCode(403)
            ->respondWithError($message, self::CODE_FORBIDDEN);
    }

    /**
     * Generates a Response with a 500 HTTP header and a given message.
     */
    public function errorInternalError(string $message = 'Internal Error'): Response
    {
        return $this->setStatusCode(500)
            ->respondWithError($message, self::CODE_INTERNAL_ERROR);
    }

    /**
     * Generates a Response with a 404 HTTP header and a given message.
     */
    public function errorNotFound(string $message = 'Resource Not Found'): Response
    {
        return $this->setStatusCode(404)
            ->respondWithError($message, self::CODE_NOT_FOUND);
    }

    /**
     * Generates a Response with a 401 HTTP header and a given message.
     */
    public function errorUnauthorized(string $message = 'Unauthorized'): Response
    {
        return $this->setStatusCode(401)
            ->respondWithError($message, self::CODE_UNAUTHORIZED);
    }

    /**
     * Generates a Response with a 400 HTTP header and a given message.
     */
    public function errorWrongArgs(string $message = 'Wrong Arguments'): Response
    {
        return $this->setStatusCode(400)
            ->respondWithError($message, self::CODE_WRONG_ARGS);
    }
}
