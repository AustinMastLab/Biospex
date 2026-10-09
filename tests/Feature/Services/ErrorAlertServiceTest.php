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

use App\Mail\ErrorAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config([
        'config.error_alerts.enabled' => true,
        'config.error_alerts.to' => 'alerts@example.org',
        'config.error_alerts.group_window_minutes' => 60,
        'config.error_alerts.max_per_hour' => 10,
    ]);
    Mail::fake();
});

afterEach(function () {
    $this->travelBack();
});

/**
 * The same failure for many records: every exception is created on the same line.
 */
function imageFailure(int $imageId): RuntimeException
{
    return new RuntimeException("OCR failed for image {$imageId}");
}

it('emails a reported error to the alert address', function () {
    report(imageFailure(1));

    Mail::assertSent(ErrorAlert::class, fn (ErrorAlert $mail) => $mail->hasTo('alerts@example.org')
        && $mail->exception->getMessage() === 'OCR failed for image 1');
});

it('sends one email for thousands of the same error', function () {
    foreach (range(1, 5000) as $imageId) {
        report(imageFailure($imageId));
    }

    Mail::assertSent(ErrorAlert::class, 1);
});

it('counts the errors it held back in the next email for that error', function () {
    foreach (range(1, 5) as $imageId) {
        report(imageFailure($imageId));
    }
    $this->travel(61)->minutes();

    report(imageFailure(6));

    Mail::assertSent(ErrorAlert::class, 2);
    Mail::assertSent(ErrorAlert::class, fn (ErrorAlert $mail) => $mail->suppressedCount === 4);
});

it('caps alert emails per hour across different errors', function () {
    config(['config.error_alerts.max_per_hour' => 3]);

    report(new RuntimeException('first'));
    report(new RuntimeException('second'));
    report(new RuntimeException('third'));
    report(new RuntimeException('fourth'));
    report(new RuntimeException('fifth'));

    Mail::assertSent(ErrorAlert::class, 3);
});

it('still logs every error', function () {
    Log::spy();

    foreach (range(1, 3) as $imageId) {
        report(imageFailure($imageId));
    }

    Log::shouldHaveReceived('error')->times(3);
});

it('sends nothing when alerts are disabled', function () {
    config(['config.error_alerts.enabled' => false]);

    report(imageFailure(1));

    Mail::assertNothingSent();
});

it('does not throw when the email cannot be sent', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP is down'));

    report(imageFailure(1));
})->throwsNoExceptions();
