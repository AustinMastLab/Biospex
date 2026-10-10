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

use App\Console\Commands\ListenerPanoptesPusherCommand;
use App\Jobs\ProcessPanoptesPusherDataJob;
use App\Mail\ErrorAlert;
use App\Mail\HealthCheckAlert;
use App\Mail\PanoptesListenerAlert;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    config(['mail.from.address' => 'alerts@example.org']);
    Mail::fake();
    Queue::fake();
    @unlink(storage_path('framework/panoptes_pusher_email.lock'));

    // Reconnect delays the listener schedules, in order
    $this->scheduled = [];
    $this->loop = Mockery::mock(LoopInterface::class);
    $this->loop->shouldReceive('addTimer')->andReturnUsing(function ($delay) {
        $this->scheduled[] = $delay;

        return Mockery::mock(TimerInterface::class);
    });

    $this->connection = Mockery::mock(WebSocket::class);
    $this->connection->shouldReceive('close')->byDefault();

    $this->listener = app(ListenerPanoptesPusherCommand::class);
    $this->listener->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));
    (function (LoopInterface $loop, WebSocket $connection) {
        $this->loop = $loop;
        $this->connection = $connection;
        $this->lastMessageTime = time();
    })->call($this->listener, $this->loop, $this->connection);
});

afterEach(function () {
    @unlink(storage_path('framework/panoptes_pusher_email.lock'));
});

/**
 * Deliver a Pusher message to the listener.
 */
function pusherMessage(ListenerPanoptesPusherCommand $listener, array $payload): void
{
    $message = Mockery::mock(MessageInterface::class);
    $message->shouldReceive('getPayload')->andReturn(json_encode($payload));

    $listener->onMessage($message);
}

function overQuota(ListenerPanoptesPusherCommand $listener): void
{
    pusherMessage($listener, ['event' => 'pusher:error', 'data' => ['code' => 4004, 'message' => 'Application is over connection quota']]);
}

function subscribed(ListenerPanoptesPusherCommand $listener): void
{
    pusherMessage($listener, ['event' => 'pusher_internal:subscription_succeeded', 'data' => '{}']);
}

function listenerEmails(string $containing): int
{
    return Mail::sent(PanoptesListenerAlert::class, fn (PanoptesListenerAlert $mail) => str_contains($mail->alertSubject, $containing))->count();
}

it('queues each classification', function () {
    pusherMessage($this->listener, ['event' => 'classification', 'data' => ['classification_id' => 778691035, 'workflow_id' => 12]]);

    Queue::assertPushed(ProcessPanoptesPusherDataJob::class);
});

it('answers a ping with a pong', function () {
    $this->connection->shouldReceive('send')->once()->with(json_encode(['event' => 'pusher:pong', 'data' => []]));

    pusherMessage($this->listener, ['event' => 'pusher:ping']);
});

it('reconnects after the connection closes unexpectedly', function () {
    $this->listener->onConnectionClose(1006, 'abnormal');

    expect($this->scheduled)->toBe([2.0]);
});

it('emails once when Notes From Nature goes over quota and once when the connection is restored', function () {
    $this->travelTo('2026-10-10 08:00:00');
    overQuota($this->listener);
    $this->travelTo('2026-10-10 08:15:00');
    overQuota($this->listener);
    $this->travelTo('2026-10-10 08:45:00');
    overQuota($this->listener);
    $this->travelTo('2026-10-10 09:45:00');
    overQuota($this->listener);
    $this->travelTo('2026-10-10 10:45:00');

    subscribed($this->listener);

    expect(listenerEmails('Pusher over quota'))->toBe(1)
        ->and(listenerEmails('Pusher connection restored'))->toBe(1)
        ->and(Mail::sent(PanoptesListenerAlert::class)->count())->toBe(2)
        ->and($this->scheduled)->toEqual([900, 1800, 3600, 3600])
        ->and(Cache::has(ListenerPanoptesPusherCommand::QUOTA_SINCE_KEY))->toBeFalse();

    Mail::assertSent(PanoptesListenerAlert::class, fn ($mail) => str_contains($mail->alertBody, 'after 2 hours 45 minutes over'));
});

it('does not email again when the listener restarts during a quota episode', function () {
    overQuota($this->listener);

    // A fresh process (for example after a deploy) hits the same quota error
    $restarted = app(ListenerPanoptesPusherCommand::class);
    $restarted->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));
    (fn (LoopInterface $loop) => $this->loop = $loop)->call($restarted, $this->loop);
    overQuota($restarted);

    expect(listenerEmails('Pusher over quota'))->toBe(1)
        ->and($this->scheduled)->toEqual([900, 1800]);
});

it('does not send a restored email after an ordinary subscription', function () {
    subscribed($this->listener);

    Mail::assertNothingSent();
});

it('goes dormant without blocking after too many errors, and emails once each way', function () {
    $this->connection->shouldReceive('close')->once();

    foreach (range(1, ListenerPanoptesPusherCommand::ERROR_THRESHOLD + 5) as $attempt) {
        $this->listener->onConnectionError(new RuntimeException("socket error {$attempt}"));
    }

    expect(listenerEmails('Listener dormant'))->toBe(1)
        ->and($this->scheduled)->toEqual([ListenerPanoptesPusherCommand::DORMANT_SECONDS]);

    subscribed($this->listener);

    expect(listenerEmails('Listener recovered'))->toBe(1)
        ->and(Cache::has(ListenerPanoptesPusherCommand::DORMANT_SINCE_KEY))->toBeFalse();
});

it('counts each Pusher error once toward the dormant threshold', function () {
    foreach (range(1, ListenerPanoptesPusherCommand::ERROR_THRESHOLD) as $attempt) {
        pusherMessage($this->listener, ['event' => 'pusher:error', 'data' => ['code' => 4200, 'message' => "reconnect {$attempt}"]]);
    }

    expect(listenerEmails('Listener dormant'))->toBe(0)
        ->and(Cache::get('panoptes_listener_errors'))->toHaveCount(ListenerPanoptesPusherCommand::ERROR_THRESHOLD);
});

it('sends other errors at most once an hour', function () {
    foreach (range(1, 3) as $attempt) {
        $this->listener->onConnectionError(new RuntimeException("socket error {$attempt}"));
    }

    expect(listenerEmails('[ERROR] Panoptes Listener'))->toBe(1);
});

it('keeps apostrophes readable in the plain-text email', function () {
    overQuota($this->listener);

    $mail = Mail::sent(PanoptesListenerAlert::class)->first();

    expect($mail->render())->toContain("Notes From Nature's Pusher account")->not->toContain('&#039;');
});

it('keeps apostrophes readable in the other plain-text alert emails', function () {
    $errorAlert = (new ErrorAlert(new RuntimeException("Can't open the subject's image"), 0, 'php artisan queue:work'))->render();
    $healthAlert = (new HealthCheckAlert(["Reverb isn't answering."]))->render();

    expect($errorAlert)->toContain("Can't open the subject's image")->not->toContain('&#039;')
        ->and($healthAlert)->toContain("Reverb isn't answering.")->not->toContain('&#039;');
});
