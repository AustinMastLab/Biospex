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
use Illuminate\Support\Str;
use Throwable;

/**
 * Plain-text email about a reported error. Sent right away, not queued,
 * so it still goes out when the queue is what's failing.
 */
class ErrorAlert extends Mailable
{
    use Queueable;

    /**
     * Create a new message instance.
     */
    public function __construct(public Throwable $exception, public int $suppressedCount, public string $context) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = sprintf('[ERROR] %s (%s): %s',
            config('app.name'),
            config('app.env'),
            Str::limit(class_basename($this->exception).': '.$this->exception->getMessage(), 120)
        );

        return $this->subject($subject)
            ->text('mail.error-alert')
            ->with([
                'exceptionClass' => get_class($this->exception),
                'exceptionMessage' => $this->exception->getMessage(),
                'location' => $this->exception->getFile().':'.$this->exception->getLine(),
                'trace' => collect(explode("\n", $this->exception->getTraceAsString()))->take(30)->implode("\n"),
            ]);
    }
}
