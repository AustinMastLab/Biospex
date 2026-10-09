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

use App\Events\BingoEvent;
use App\Events\ScoreboardEvent;
use App\Events\WeDigBioProgressEvent;
use App\Models\Bingo;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;

dataset('live update events', [
    'scoreboard' => fn () => new ScoreboardEvent(1, ['teams' => []]),
    'WeDigBio progress' => fn () => new WeDigBioProgressEvent('event-uuid', ['total' => 0]),
    'bingo' => fn () => new BingoEvent(Bingo::factory()->create(), '{}'),
]);

it('broadcasts right away instead of queueing a broadcast job', function (object $event) {
    Queue::fake();

    event($event);

    Queue::assertNothingPushed();
})->with('live update events');

it('reports a broadcasting failure instead of throwing', function (object $event) {
    Broadcast::extend('failing', fn () => new class implements Broadcaster
    {
        public function auth($request): mixed
        {
            return null;
        }

        public function validAuthenticationResponse($request, $result): mixed
        {
            return null;
        }

        public function broadcast(array $channels, $event, array $payload = []): void
        {
            throw new BroadcastException('Pusher error: 502 Bad Gateway');
        }
    });
    config([
        'broadcasting.connections.failing' => ['driver' => 'failing'],
        'broadcasting.default' => 'failing',
    ]);
    Exceptions::fake();

    event($event);

    Exceptions::assertReported(BroadcastException::class);
})->with('live update events');
