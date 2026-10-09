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

namespace App\Services;

use App\Mail\ErrorAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Emails reported errors, limited so a burst of failures can't flood the mailbox.
 *
 * Errors are grouped by exception class, file and line, since messages often hold
 * record IDs. Each group sends at most one email per window, all groups together
 * at most config('config.error_alerts.max_per_hour'), and the next email for a group
 * says how many times it happened in between. Logging is not affected.
 */
class ErrorAlertService
{
    /**
     * Rate limiter key shared by all error alert emails.
     */
    public const RATE_LIMITER_KEY = 'error-alerts';

    /**
     * Email the exception unless its group, or all alerts together, are over their limit.
     */
    public function report(Throwable $exception): void
    {
        $settings = config('config.error_alerts');
        $recipient = $settings['to'] ?: config('mail.from.address');

        if (! $settings['enabled'] || empty($recipient)) {
            return;
        }

        try {
            $group = sha1(get_class($exception).'|'.$exception->getFile().'|'.$exception->getLine());
            $sentKey = "error-alert:{$group}:sent";
            $suppressedKey = "error-alert:{$group}:suppressed";

            if (! Cache::add($sentKey, true, now()->addMinutes($settings['group_window_minutes']))) {
                $this->countSuppressed($suppressedKey);

                return;
            }

            if (RateLimiter::tooManyAttempts(self::RATE_LIMITER_KEY, $settings['max_per_hour'])) {
                // Let this group email again once the hourly cap clears.
                Cache::forget($sentKey);
                $this->countSuppressed($suppressedKey);

                return;
            }

            RateLimiter::hit(self::RATE_LIMITER_KEY, 3600);

            Mail::to($recipient)->send(new ErrorAlert(
                $exception,
                (int) Cache::pull($suppressedKey, 0),
                $this->context(),
            ));
        } catch (Throwable $alertFailure) {
            Log::warning('Could not send error alert email', ['error' => $alertFailure->getMessage()]);
        }
    }

    /**
     * Count an occurrence that wasn't emailed, for the group's next email.
     */
    protected function countSuppressed(string $key): void
    {
        Cache::add($key, 0, now()->addDay());
        Cache::increment($key);
    }

    /**
     * Where the error happened: the request URL, or the console command.
     */
    protected function context(): string
    {
        if (app()->runningInConsole()) {
            return 'php '.implode(' ', $_SERVER['argv'] ?? ['artisan']);
        }

        return request()->method().' '.request()->fullUrl();
    }
}
