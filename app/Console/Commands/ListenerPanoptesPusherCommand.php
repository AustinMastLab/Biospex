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

namespace App\Console\Commands;

use App\Jobs\ProcessPanoptesPusherDataJob;
use App\Mail\PanoptesListenerAlert;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;

use function Ratchet\Client\connect;

/**
 * Listens to the Notes From Nature (Zooniverse) Pusher channel and queues each classification.
 *
 * Alerts are sent when the state changes, not on every failure:
 * - Over quota (Pusher error 4004, Notes From Nature's limit): one email when it starts, quiet retries
 *   with a growing delay, and one email when the subscription succeeds again.
 * - Too many errors in a minute: the listener goes dormant for an hour without blocking the event loop,
 *   with one email when it goes dormant and one when it recovers.
 * Other errors send at most one email an hour.
 */
class ListenerPanoptesPusherCommand extends Command
{
    /**
     * Cache key holding when the current over-quota episode started.
     */
    public const QUOTA_SINCE_KEY = 'panoptes_listener_quota_since';

    /**
     * Cache key holding how many reconnects the current over-quota episode has tried.
     */
    public const QUOTA_RETRIES_KEY = 'panoptes_listener_quota_retries';

    /**
     * Cache key holding when the listener went dormant after too many errors.
     */
    public const DORMANT_SINCE_KEY = 'panoptes_listener_dormant_since';

    /**
     * Seconds to wait before each reconnect while over quota (the last value repeats).
     */
    public const QUOTA_RETRY_DELAYS = [900, 1800, 3600];

    /**
     * Seconds to stay dormant after too many errors.
     */
    public const DORMANT_SECONDS = 3600;

    /**
     * Errors within a minute that put the listener to sleep.
     */
    public const ERROR_THRESHOLD = 10;

    protected $signature = 'panoptes:listen';

    protected $description = 'Listen to external Panoptes Pusher channel';

    private LoopInterface $loop;

    private ?WebSocket $connection = null;

    private bool $isShuttingDown = false;

    /**
     * True while waiting out a quota episode or dormant hour; heartbeat reconnects are suspended.
     */
    private bool $isPaused = false;

    private int $maxReconnectAttempts = 10;

    private int $reconnectAttempts = 0;

    private int $reconnectDelay = 1;

    private int $lastMessageTime;

    private mixed $heartbeatTimer = null;

    private string $adminEmail;

    private bool $intentionalDisconnect = false;

    public function __construct()
    {
        parent::__construct();
        $this->adminEmail = (string) config('mail.from.address');
    }

    public function handle(): int
    {
        try {
            $this->validateConfiguration();
            $this->initializeListener();

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->handleCriticalError('Failed to start Panoptes listener', $e);

            return self::FAILURE;
        }
    }

    private function validateConfiguration(): void
    {
        $required = [
            'zooniverse.pusher.id' => 'ZOONIVERSE_PUSHER_ID',
            'zooniverse.pusher.cluster' => 'ZOONIVERSE_PUSHER_CLUSTER',
            'zooniverse.pusher.channel' => 'ZOONIVERSE_PUSHER_CHANNEL',
        ];

        foreach ($required as $configKey => $envKey) {
            if (empty(config($configKey))) {
                throw new \RuntimeException("Missing required configuration: {$configKey} (env: {$envKey})");
            }
        }

        if (empty($this->adminEmail)) {
            $this->warn('Admin email not configured - error notifications will be logged only');
        }
    }

    private function initializeListener(): void
    {
        $this->loop = Loop::get();
        $this->lastMessageTime = time();

        $this->setupHeartbeatMonitor();
        $this->setupSignalHandlers();
        $this->connectToPusher();

        $this->loop->run();
    }

    private function setupHeartbeatMonitor(): void
    {
        $this->heartbeatTimer = $this->loop->addPeriodicTimer(30, function () {
            if ($this->isShuttingDown || $this->isPaused) {
                return;
            }

            if ((time() - $this->lastMessageTime) > 120) {
                $this->forceReconnection();
            }
        });
    }

    private function connectToPusher(): void
    {
        if ($this->isShuttingDown) {
            return;
        }

        $this->isPaused = false;
        $this->intentionalDisconnect = false;
        $this->lastMessageTime = time();
        $url = $this->buildWebSocketUrl();

        $connector = new Connector([
            'timeout' => 20,
            'tls' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        connect($url, [], [], $this->loop, $connector)
            ->then(
                [$this, 'onConnectionSuccess'],
                [$this, 'onConnectionFailure']
            );
    }

    private function buildWebSocketUrl(): string
    {
        $cluster = config('zooniverse.pusher.cluster');
        $appId = config('zooniverse.pusher.id');

        return "wss://ws-{$cluster}.pusher.com:443/app/{$appId}?protocol=7&client=php-ratchet&version=1.0";
    }

    public function onConnectionSuccess(WebSocket $connection): void
    {
        $this->connection = $connection;
        $this->reconnectAttempts = 0;
        $this->reconnectDelay = 1;
        $this->lastMessageTime = time();

        $this->subscribeToChannel();
        $this->setupConnectionEventHandlers();
    }

    public function onConnectionFailure(\Throwable $e): void
    {
        $this->reconnectAttempts++;
        $this->handleError("Connection failed (attempt {$this->reconnectAttempts})", $e);

        if ($this->reconnectAttempts > $this->maxReconnectAttempts) {
            $this->handleCriticalError("Max reconnection attempts ({$this->maxReconnectAttempts}) exceeded", $e);
            $this->shutdown(1);

            return;
        }

        $jitter = mt_rand(0, 1000) / 1000;
        $delay = min(60, $this->reconnectDelay * pow(2, $this->reconnectAttempts - 1)) + $jitter;

        $this->loop->addTimer($delay, function () {
            $this->connectToPusher();
        });
    }

    private function subscribeToChannel(): void
    {
        $channel = config('zooniverse.pusher.channel');

        try {
            $this->connection->send(json_encode([
                'event' => 'pusher:subscribe',
                'data' => ['channel' => $channel],
            ]));
        } catch (\Throwable $e) {
            $this->handleError("Failed to subscribe to channel: {$channel}", $e);
        }
    }

    private function setupConnectionEventHandlers(): void
    {
        $this->connection->on('message', [$this, 'onMessage']);
        $this->connection->on('close', [$this, 'onConnectionClose']);
        $this->connection->on('error', [$this, 'onConnectionError']);
    }

    public function onMessage(MessageInterface $msg): void
    {
        $this->lastMessageTime = time();

        try {
            $rawPayload = $msg->getPayload();
            if (empty($rawPayload)) {
                return;
            }

            $payload = json_decode($rawPayload, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($payload)) {
                return;
            }

            $event = $payload['event'] ?? 'unknown';

            switch ($event) {
                case 'pusher:ping':
                    $this->handlePing();
                    break;
                case 'pusher_internal:subscription_succeeded':
                    $this->handleSubscriptionSucceeded();
                    break;
                case 'classification':
                    $this->handleClassificationEvent($payload);
                    break;
                case 'pusher:error':
                    $this->handlePusherError($payload);
                    break;
            }
        } catch (\Throwable $e) {
            $this->handleError('Error processing incoming message', $e);
        }
    }

    public function onConnectionClose($code = null, $reason = null): void
    {
        if ($this->intentionalDisconnect || $this->isShuttingDown) {
            return;
        }

        $this->warn("Connection closed unexpectedly (Code: {$code}, Reason: {$reason})");
        $this->scheduleReconnection(2.0);
    }

    public function onConnectionError(\Throwable $e): void
    {
        $this->handleError('Connection error during operation', $e);
    }

    private function handlePing(): void
    {
        try {
            $this->connection->send(json_encode(['event' => 'pusher:pong', 'data' => []]));
            $this->lastMessageTime = time();
        } catch (\Throwable $e) {
            $this->forceReconnection();
        }
    }

    /**
     * Subscribed again: close any over-quota or dormant episode and say it's over.
     */
    private function handleSubscriptionSucceeded(): void
    {
        $quotaSince = Cache::pull(self::QUOTA_SINCE_KEY);
        Cache::forget(self::QUOTA_RETRIES_KEY);

        if ($quotaSince !== null) {
            $duration = CarbonInterval::seconds(max(0, now()->getTimestamp() - (int) $quotaSince))->cascade()->forHumans(['parts' => 2]);
            Log::info("Panoptes Pusher connection restored after {$duration} over quota.");
            $this->sendStateChangeEmail(
                'Pusher connection restored',
                "The Panoptes Pusher connection is working again after {$duration} over Notes From Nature's quota.\n"
                .'Live classifications are flowing again. Classifications from the gap are filled in by the nightly reconcile.'
            );
        }

        $dormantSince = Cache::pull(self::DORMANT_SINCE_KEY);

        if ($dormantSince !== null) {
            Log::info('Panoptes listener recovered from dormant mode.');
            $this->sendStateChangeEmail(
                'Listener recovered',
                "The Panoptes listener has reconnected after going dormant because of repeated errors.\n"
                .'Live classifications are flowing again.'
            );
        }
    }

    private function handleClassificationEvent(array $payload): void
    {
        try {
            $data = $payload['data'];
            if (is_array($data)) {
                $data = json_encode($data);
            }
            ProcessPanoptesPusherDataJob::dispatch($data);
        } catch (\Throwable $e) {
            $this->handleError('Failed to dispatch classification job', $e);
        }
    }

    private function handlePusherError(array $payload): void
    {
        $errorMessage = $payload['data']['message'] ?? 'Unknown Pusher error';
        $errorCode = $payload['data']['code'] ?? 'unknown';

        switch ($errorCode) {
            case 4004: // Over quota (Notes From Nature's Pusher account)
                $this->handleOverQuota($errorMessage);
                break;

            case 4001:
            case 4003:
                $this->handleCriticalError('Pusher configuration error', new \RuntimeException($errorMessage));
                $this->shutdown(1);
                break;

            default:
                $this->handleError("Pusher error: {$errorMessage}", null, $payload);
        }
    }

    /**
     * Over quota: email once when the episode starts, then retry quietly with a growing delay.
     */
    private function handleOverQuota(string $errorMessage): void
    {
        $this->isPaused = true;
        $this->intentionalDisconnect = true;

        if (Cache::add(self::QUOTA_SINCE_KEY, now()->getTimestamp(), now()->addDays(30))) {
            Log::warning("Panoptes Pusher over quota: {$errorMessage}");
            $this->sendStateChangeEmail(
                'Pusher over quota',
                "Notes From Nature's Pusher account is over its quota ({$errorMessage}).\n"
                ."Live classifications are paused. The listener retries quietly and emails again when the connection is restored.\n"
                .'Classifications from the gap are filled in by the nightly reconcile.'
            );
        }

        $retries = (int) Cache::get(self::QUOTA_RETRIES_KEY, 0);
        $delay = self::QUOTA_RETRY_DELAYS[min($retries, count(self::QUOTA_RETRY_DELAYS) - 1)];
        Cache::put(self::QUOTA_RETRIES_KEY, $retries + 1, now()->addDays(30));

        Log::info("Panoptes Pusher still over quota; retrying in {$delay} seconds.");
        $this->scheduleReconnection($delay);
    }

    private function forceReconnection(): void
    {
        try {
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (\Throwable $e) {
        }
        $this->scheduleReconnection(1.0);
    }

    private function scheduleReconnection(float $delay = 1.0): void
    {
        if ($this->isShuttingDown) {
            return;
        }
        $this->loop->addTimer($delay, function () {
            $this->connectToPusher();
        });
    }

    /**
     * Count an error; more than ERROR_THRESHOLD in a minute puts the listener to sleep for an hour.
     */
    private function trackError(): void
    {
        if ($this->isPaused) {
            return;
        }

        $key = 'panoptes_listener_errors';
        $errors = array_filter(Cache::get($key, []), fn ($t) => $t > (time() - 60));
        $errors[] = time();
        Cache::put($key, $errors, 70);

        if (count($errors) > self::ERROR_THRESHOLD) {
            Cache::forget($key);
            $this->goDormant();
        }
    }

    /**
     * Stop and wait an hour without blocking: the loop keeps handling signals and timers.
     */
    private function goDormant(): void
    {
        $this->isPaused = true;
        $this->intentionalDisconnect = true;

        try {
            $this->connection?->close();
        } catch (\Throwable $e) {
        }

        if (Cache::add(self::DORMANT_SINCE_KEY, now()->getTimestamp(), now()->addDays(1))) {
            Log::critical('Too many Panoptes listener errors; dormant for one hour.');
            $this->sendStateChangeEmail(
                'Listener dormant',
                'The Panoptes listener hit more than '.self::ERROR_THRESHOLD." errors in a minute and stopped for one hour.\n"
                .'It reconnects automatically and emails again when it recovers. Check the log for the errors.'
            );
        }

        $this->scheduleReconnection(self::DORMANT_SECONDS);
    }

    private function handleError(string $message, ?\Throwable $e = null, array $context = []): void
    {
        if ($this->isPaused) {
            Log::info($message, $context + ['error' => $e?->getMessage()]);

            return;
        }

        $this->trackError();

        $context['timestamp'] = now()->toISOString();
        Log::error($message, $context);
        $this->error($message);

        $this->sendErrorEmail($message, $e, $context);
    }

    private function handleCriticalError(string $message, \Throwable $e): void
    {
        $context = ['timestamp' => now()->toISOString(), 'error' => $e->getMessage()];
        Log::critical($message, $context);
        $this->error("🚨 CRITICAL: {$message}");

        $this->sendErrorEmail($message, $e, $context, true);
    }

    /**
     * Email about an error, at most once an hour (a file lock, so it survives restarts and cache clears).
     */
    private function sendErrorEmail(string $message, ?\Throwable $e, array $context, bool $isCritical = false): void
    {
        $lockFile = storage_path('framework/panoptes_pusher_email.lock');

        if (file_exists($lockFile) && (time() - (int) file_get_contents($lockFile)) < 3600) {
            return;
        }

        file_put_contents($lockFile, time());

        $this->sendEmail(
            ($isCritical ? '[CRITICAL] ' : '[ERROR] ').'Panoptes Listener - '.config('app.name'),
            $this->buildErrorEmailBody($message, $e, $context, $isCritical)
        );
    }

    /**
     * Email about a change of state (over quota, restored, dormant, recovered). Not rate limited:
     * the episode keys in the cache make sure each change is announced once.
     */
    private function sendStateChangeEmail(string $title, string $body): void
    {
        $this->sendEmail(
            "[NOTICE] Panoptes Listener: {$title} - ".config('app.name'),
            $body."\n\nTime: ".now()->format('Y-m-d H:i:s T')
        );
    }

    private function sendEmail(string $subject, string $body): void
    {
        if (empty($this->adminEmail)) {
            return;
        }

        try {
            Mail::to($this->adminEmail)->send(new PanoptesListenerAlert($subject, $body));
        } catch (\Throwable $me) {
            Log::error('Failed to send Panoptes listener email', ['mail_error' => $me->getMessage()]);
        }
    }

    private function buildErrorEmailBody(string $message, ?\Throwable $e, array $context, bool $isCritical): string
    {
        $body = "Panoptes Listener Error Report\n".str_repeat('=', 50)."\n\n";
        if ($isCritical) {
            $body .= "🚨 CRITICAL ERROR - Immediate attention required!\n\n";
        }

        $body .= 'Time: '.now()->format('Y-m-d H:i:s T')."\n";
        $body .= "Error Message: {$message}\n\n";

        if ($e) {
            $body .= 'Exception: '.get_class($e)."\nMessage: ".$e->getMessage()."\n";
            $body .= 'File: '.$e->getFile().':'.$e->getLine()."\n\n";
            $body .= "Stack Trace:\n".$e->getTraceAsString()."\n\n";
        }

        if (! empty($context)) {
            $body .= "Context:\n".json_encode($context, JSON_PRETTY_PRINT)."\n\n";
        }

        return $body;
    }

    private function setupSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }
        foreach ([SIGINT, SIGTERM, SIGHUP] as $s) {
            $this->loop->addSignal($s, fn () => $this->shutdown(0));
        }
    }

    private function shutdown(int $exitCode = 0): void
    {
        $this->isShuttingDown = true;
        if ($this->heartbeatTimer) {
            $this->loop->cancelTimer($this->heartbeatTimer);
        }
        try {
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (\Throwable $e) {
        }
        $this->loop->stop();
        exit($exitCode);
    }
}
