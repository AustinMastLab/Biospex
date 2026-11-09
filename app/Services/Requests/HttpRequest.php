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

namespace App\Services\Requests;

use DateTime;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Pool;
use GuzzleHttp\RetryMiddleware;
use Illuminate\Support\Facades\Cache;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpRequest
{
    /**
     * OAuth2 Generic Provider instance for authentication
     */
    protected GenericProvider $provider;

    /**
     * Current OAuth2 access token string
     */
    protected ?string $accessToken = null;

    /**
     * Maximum number of retry attempts for failed requests
     */
    protected int $maxRetries = 3;

    /**
     * HTTP status codes worth retrying: rate limiting and temporary server errors.
     *
     * @var array<int, int>
     */
    protected const RETRY_STATUS_CODES = [429, 500, 502, 503, 504];

    /**
     * Set authentication provider with retry middleware
     *
     * The retrying Guzzle client is given to the provider as its httpClient collaborator.
     * The provider ignores a "handler" option, so passing the stack that way left every
     * token and API request without retries.
     *
     * @param  array  $config  Configuration options for the OAuth2 provider
     */
    public function setHttpProvider(array $config = []): GenericProvider
    {
        $handlerStack = HandlerStack::create($this->transportHandler());
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));

        $reqOpts = array_merge([
            'urlAccessToken' => '',
            'urlAuthorize' => '',
            'urlResourceOwnerDetails' => '',
        ], $config);

        return $this->provider = new GenericProvider($reqOpts, [
            'httpClient' => new Client(['handler' => $handlerStack]),
        ]);
    }

    /**
     * The handler that sends requests, below the retry middleware.
     */
    protected function transportHandler(): callable
    {
        return new CurlHandler;
    }

    /**
     * Get a configured HTTP client from the OAuth2 provider
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->provider->getHttpClient();
    }

    /**
     * Obtain and cache a new access token
     *
     * @param  string  $token  Cache key for storing the access token
     *
     * @throws IdentityProviderException
     * @throws GuzzleException
     */
    protected function setAccessToken(string $token): void
    {
        $accessToken = $this->provider->getAccessToken('client_credentials');

        Cache::put($token, [
            'access_token' => $accessToken->getToken(),
            'expires_at' => $accessToken->getExpires(),
        ], 720);

        $this->accessToken = $accessToken->getToken();
    }

    /**
     * Verify and refresh the access token if expired
     *
     * @param  string  $token
     *
     * @throws GuzzleException
     * @throws IdentityProviderException
     */
    public function checkAccessToken($token): void
    {
        $cachedToken = Cache::get($token);

        if (
            ! is_array($cachedToken) ||
            empty($cachedToken['access_token']) ||
            empty($cachedToken['expires_at']) ||
            time() >= (int) $cachedToken['expires_at']
        ) {
            $this->setAccessToken($token);

            return;
        }

        $this->accessToken = $cachedToken['access_token'];
    }

    /**
     * Create an authenticated request with the current access token
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array  $options  Additional request options
     */
    protected function buildAuthenticatedRequest(string $method, string $uri, array $options = []): RequestInterface
    {
        if ($this->accessToken === null) {
            throw new \RuntimeException('Access token has not been initialized.');
        }

        return $this->provider->getAuthenticatedRequest($method, $uri, $this->accessToken, $options);
    }

    /**
     * Create a request pool for concurrent requests
     *
     * @param  Generator  $promises  Generator of request promises
     * @param  array  $poolConfig  Pool configuration options
     */
    public function pool(Generator $promises, array $poolConfig): Pool
    {
        return new Pool($this->getHttpClient(), $promises, $poolConfig);
    }

    /**
     * Create retry decision callback for failed requests
     *
     * Retries requests on:
     * - Server errors (500, 502, 503, 504)
     * - Rate limiting (429)
     * - Connection timeouts and network errors (no response at all)
     *
     * Other responses, such as a 404 or 401, are not retried. With http_errors on, an
     * error response arrives as a RequestException, so its status is read from there.
     */
    public function retryDecider(): \Closure
    {
        return function (int $retries, RequestInterface $request, ?ResponseInterface $response = null, mixed $exception = null): bool {
            if ($retries >= $this->maxRetries) {
                return false;
            }

            $response ??= $exception instanceof RequestException ? $exception->getResponse() : null;

            if ($response !== null) {
                return in_array($response->getStatusCode(), self::RETRY_STATUS_CODES, true);
            }

            return $exception instanceof TransferException;
        };
    }

    /**
     * Create a retry delay callback with Retry-After header support
     *
     * Uses server's Retry-After header when available, otherwise
     * implements exponential backoff strategy
     */
    public function retryDelay(): \Closure
    {
        return function (int $retries, $response = null): int {
            if ($response && $response->hasHeader('Retry-After')) {
                $retryAfter = $response->getHeaderLine('Retry-After');

                if (! is_numeric($retryAfter)) {
                    $retryAfter = (new DateTime($retryAfter))->getTimestamp() - time();
                }

                return (int) $retryAfter * 1000;
            }

            // Exponential backoff: 1s, 2s, 4s, etc.
            return RetryMiddleware::exponentialDelay($retries);
        };
    }

    /**
     * Set maximum number of retry attempts
     *
     * @param  int  $maxRetries  Maximum number of retries
     * @return $this
     */
    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;

        return $this;
    }

    /**
     * Create a standalone Guzzle HTTP client with retry middleware
     *
     * @param  array  $config  Additional client configuration
     */
    public function createDirectHttpClient(array $config = []): Client
    {
        $handlerStack = HandlerStack::create(new CurlHandler);
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));

        $defaultConfig = [
            'handler' => $handlerStack,
            'timeout' => 60,
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'Biospex/1.0',
            ],
        ];

        return new Client(array_merge($defaultConfig, $config));
    }
}
