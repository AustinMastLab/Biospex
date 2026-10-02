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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FlashHelperMessage
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // If there's a flash message, tell the Cache middleware (running next) to skip this request
        if (session()->hasAny(['success', 'info', 'warning', 'danger'])) {
            $request->attributes->set('responsecache.doNotCache', true);
        }

        $response = $next($request);

        if ($request->isMethod('GET')) {
            $status = ['success', 'info', 'warning', 'danger'];

            foreach ($status as $type) {
                if (session()->has($type)) {
                    // 2. Also set the header on the outgoing response for safety
                    $response->headers->set('laravel-responsecache', 'do-not-cache');

                    $payload = json_encode([
                        'type' => $type,
                        'message' => (string) session($type),
                        'icon' => match ($type) {
                            'success' => 'check-circle',
                            'info' => 'info-circle',
                            'warning' => 'exclamation-circle',
                            'danger' => 'times-circle',
                            default => 'info-circle',
                        },
                    ]);

                    // Create a simple session cookie (minutes=0)
                    // No domain, No secure (for local testing), No httpOnly
                    $domain = config('session.domain');
                    $response->withCookie(cookie('app_flash', $payload, 0, '/', $domain, false, false, false, 'Lax'));

                    session()->forget($type);

                    // Forget the session key so it doesn't persist
                    session()->forget($type);
                    break;
                }
            }
        }

        return $response;
    }
}
