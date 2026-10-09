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

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/**
 * Plain-text email listing failed health checks. Sent right away, not queued,
 * since the queue workers may be what's down.
 */
class HealthCheckAlert extends Mailable
{
    use Queueable;

    /**
     * Create a new message instance.
     *
     * @param  array<int, string>  $problems
     */
    public function __construct(public array $problems) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(sprintf('[HEALTH CHECK] %s (%s): %d %s',
            config('app.name'),
            config('app.env'),
            count($this->problems),
            count($this->problems) === 1 ? 'problem' : 'problems'
        ))->text('mail.health-check-alert');
    }
}
