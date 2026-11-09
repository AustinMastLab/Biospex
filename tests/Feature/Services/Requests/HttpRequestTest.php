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

use App\Services\Requests\HttpRequest;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

/**
 * An HttpRequest that answers from a queue of mock responses, without waiting between retries.
 */
function httpRequestAnswering(MockHandler $mock): HttpRequest
{
    $request = new class extends HttpRequest
    {
        public MockHandler $mock;

        protected function transportHandler(): callable
        {
            return $this->mock;
        }

        public function retryDelay(): Closure
        {
            return fn (): int => 0;
        }
    };
    $request->mock = $mock;
    $request->setHttpProvider(['urlAccessToken' => 'https://panoptes.example.org/oauth/token', 'clientId' => 'id', 'clientSecret' => 'secret']);

    return $request;
}

it('retries an API request after a server error and returns the next response', function () {
    $mock = new MockHandler([new Response(502), new Response(504), new Response(200, [], '{"users":[]}')]);
    $request = httpRequestAnswering($mock);

    $response = $request->getHttpClient()->request('GET', 'https://panoptes.example.org/api/users/1');

    expect($response->getStatusCode())->toBe(200)
        ->and($mock->count())->toBe(0);
});

it('retries the access token request after a server error page', function () {
    $mock = new MockHandler([
        new Response(503, ['Content-Type' => 'text/html'], '<html>Service Unavailable</html>'),
        new Response(200, ['Content-Type' => 'application/json'], '{"access_token":"abc","expires_in":7200}'),
    ]);
    $request = httpRequestAnswering($mock);

    $token = $request->setHttpProvider(['urlAccessToken' => 'https://panoptes.example.org/oauth/token', 'clientId' => 'id', 'clientSecret' => 'secret'])
        ->getAccessToken('client_credentials');

    expect($token->getToken())->toBe('abc');
});

it('does not retry a request that is not found', function () {
    $mock = new MockHandler([new Response(404), new Response(200)]);
    $request = httpRequestAnswering($mock);

    expect(fn () => $request->getHttpClient()->request('GET', 'https://panoptes.example.org/api/users/1'))
        ->toThrow(ClientException::class);
    expect($mock->count())->toBe(1);
});

it('gives up after three retries', function () {
    $mock = new MockHandler([new Response(503), new Response(503), new Response(503), new Response(503), new Response(200)]);
    $request = httpRequestAnswering($mock);

    expect(fn () => $request->getHttpClient()->request('GET', 'https://panoptes.example.org/api/users/1'))
        ->toThrow(ServerException::class);
    expect($mock->count())->toBe(1);
});
