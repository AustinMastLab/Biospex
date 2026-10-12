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

use App\Mail\HealthCheckAlert;
use App\Services\SupervisorControlService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

const PING_URL = 'https://hc-ping.com/test-check';

beforeEach(function () {
    config([
        'app.tag' => 'biospex',
        'services.aws.sqs' => ['batch_update' => 'dev-batch-update', 'ocr_update' => 'dev-ocr-update'],
        'config.panoptes_listener' => 'panoptes-pusher',
        'config.panoptes_listener_enabled' => 0,
        'config.health_check_ping_url' => PING_URL,
        'config.error_alerts.enabled' => true,
        'config.error_alerts.to' => 'alerts@example.org',
        'broadcasting.connections.reverb.app_id' => 'dev.biospex',
        'broadcasting.connections.reverb.options' => ['host' => 'dev.biospex.org', 'port' => 443, 'scheme' => 'https'],
    ]);
    Mail::fake();
    $this->reverbStatus = 401;
    Http::fake([
        'dev.biospex.org/*' => fn () => Http::response('', $this->reverbStatus),
        'hc-ping.com/*' => Http::response('OK'),
    ]);
});

/**
 * @param  array<string, string>  $states
 */
function supervisorStates(array $states): void
{
    test()->mock(SupervisorControlService::class)
        ->shouldReceive('processStates')
        ->andReturn($states);
}

it('pings Healthchecks.io when everything is running', function () {
    supervisorStates(['default_00' => 'RUNNING', 'biospex-reverb_00' => 'RUNNING']);

    $this->artisan('app:health-check')->assertSuccessful();

    Http::assertSent(fn (HttpRequest $request) => $request->url() === PING_URL);
    Mail::assertNothingSent();
});

it('ignores stopped SQS listeners and a disabled Panoptes listener', function () {
    supervisorStates([
        'default_00' => 'RUNNING',
        'dev-batch-update' => 'STOPPED',
        'dev-ocr-update' => 'EXITED',
        'panoptes-pusher' => 'STOPPED',
    ]);

    $this->artisan('app:health-check')->assertSuccessful();
});

it('alerts about a stopped Panoptes listener when it is enabled', function () {
    config(['config.panoptes_listener_enabled' => 1]);
    supervisorStates(['default_00' => 'RUNNING', 'panoptes-pusher' => 'STOPPED']);

    $this->artisan('app:health-check')->assertFailed();
    $this->artisan('app:health-check')->assertFailed();

    Mail::assertSent(HealthCheckAlert::class, fn (HealthCheckAlert $mail) => $mail->problems === ['panoptes-pusher is STOPPED.']);
});

it('waits for a second failed check before alerting', function () {
    supervisorStates(['default_00' => 'FATAL', 'biospex-reverb_00' => 'RUNNING']);

    $this->artisan('app:health-check')->assertFailed();

    Mail::assertNothingSent();
    Http::assertNotSent(fn (HttpRequest $request) => str_starts_with($request->url(), PING_URL));

    $this->artisan('app:health-check')->assertFailed();

    Mail::assertSent(HealthCheckAlert::class, fn (HealthCheckAlert $mail) => $mail->hasTo('alerts@example.org')
        && $mail->problems === ['default_00 is FATAL.']);
    Http::assertSent(fn (HttpRequest $request) => $request->url() === PING_URL.'/fail');
});

it('reports Reverb as down when it returns a server error', function () {
    supervisorStates(['default_00' => 'RUNNING']);
    $this->reverbStatus = 502;

    $this->artisan('app:health-check')->assertFailed();
    $this->artisan('app:health-check')->assertFailed();

    Mail::assertSent(HealthCheckAlert::class, fn (HealthCheckAlert $mail) => str_contains($mail->problems[0], 'Reverb returned HTTP 502'));
});

it('reports when Supervisor cannot be reached', function () {
    test()->mock(SupervisorControlService::class)
        ->shouldReceive('processStates')
        ->andThrow(new RuntimeException('connection refused'));

    $this->artisan('app:health-check')->assertFailed();
    $this->artisan('app:health-check')->assertFailed();

    Mail::assertSent(HealthCheckAlert::class, fn (HealthCheckAlert $mail) => $mail->problems === ['Supervisor could not be reached: connection refused']);
});
