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

namespace App\Console\Commands;

use App\Mail\HealthCheckAlert;
use App\Services\SupervisorControlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Checks that the queue workers, Reverb and (when enabled) the Panoptes listener are running.
 *
 * SQS listeners are skipped: they are started on demand and stop themselves when idle.
 * A problem is only reported when the previous check failed too, so the few seconds
 * of restarts during a deploy don't raise an alarm. When healthy, the Healthchecks.io
 * ping URL is pinged; when a problem is confirmed, its /fail endpoint is.
 */
class AppHealthCheckCommand extends Command
{
    /**
     * Cache key set while checks are failing, to confirm a problem on the next run.
     */
    public const FAILING_CACHE_KEY = 'health-check:failing';

    /**
     * Supervisor states that count as up.
     */
    protected const HEALTHY_STATES = ['RUNNING', 'STARTING'];

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'app:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check that the queue workers, Reverb and the Panoptes listener are running, and alert if not.';

    /**
     * Execute the console command.
     */
    public function handle(SupervisorControlService $supervisor): int
    {
        $problems = [...$this->checkProcesses($supervisor), ...$this->checkReverb()];
        $pingUrl = config('config.health_check_ping_url');

        if ($problems === []) {
            Cache::forget(self::FAILING_CACHE_KEY);
            $this->ping($pingUrl);
            $this->info('All checks passed.');

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->error($problem);
        }

        if (! Cache::has(self::FAILING_CACHE_KEY)) {
            // First failed check: wait for the next run to confirm it.
            Cache::put(self::FAILING_CACHE_KEY, true, now()->addMinutes(15));

            return self::FAILURE;
        }

        $this->ping($pingUrl ? rtrim($pingUrl, '/').'/fail' : null, implode("\n", $problems));
        $this->sendAlert($problems);

        return self::FAILURE;
    }

    /**
     * Supervisor programs in this app's group that should be running but aren't.
     *
     * @return array<int, string>
     */
    protected function checkProcesses(SupervisorControlService $supervisor): array
    {
        try {
            $states = $supervisor->processStates();
        } catch (Throwable $e) {
            return ['Supervisor could not be reached: '.$e->getMessage()];
        }

        if ($states === []) {
            return ['Supervisor has no programs in the "'.config('app.tag').'" group.'];
        }

        $onDemand = array_values(config('services.aws.sqs', []));

        if (! config('config.panoptes_listener_enabled')) {
            $onDemand[] = config('config.panoptes_listener');
        }

        $problems = [];

        foreach ($states as $program => $state) {
            if (in_array($program, $onDemand, true) || in_array($state, self::HEALTHY_STATES, true)) {
                continue;
            }

            $problems[] = "{$program} is {$state}.";
        }

        return $problems;
    }

    /**
     * Whether Reverb answers. Without credentials it returns 401 when up; nginx returns 502 when it's down.
     *
     * @return array<int, string>
     */
    protected function checkReverb(): array
    {
        $options = config('broadcasting.connections.reverb.options');
        $appId = config('broadcasting.connections.reverb.app_id');

        if (empty($options['host']) || empty($appId)) {
            return [];
        }

        $url = sprintf('%s://%s:%s/apps/%s/channels', $options['scheme'], $options['host'], $options['port'], $appId);

        try {
            $status = Http::timeout(10)->get($url)->status();
        } catch (Throwable $e) {
            return ["Reverb could not be reached at {$url}: {$e->getMessage()}"];
        }

        return $status >= 500 ? ["Reverb returned HTTP {$status} at {$url}."] : [];
    }

    /**
     * Ping Healthchecks.io, if configured. A failed ping only gets logged.
     */
    protected function ping(?string $url, string $body = ''): void
    {
        if (empty($url)) {
            return;
        }

        try {
            Http::timeout(10)->withBody($body, 'text/plain')->post($url);
        } catch (Throwable $e) {
            Log::warning('Health check ping failed', ['url' => $url, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Email the confirmed problems, at most once an hour for the same set.
     *
     * @param  array<int, string>  $problems
     */
    protected function sendAlert(array $problems): void
    {
        $recipient = config('config.error_alerts.to') ?: config('mail.from.address');

        if (! config('config.error_alerts.enabled') || empty($recipient)) {
            return;
        }

        if (! Cache::add('health-check:alerted:'.sha1(implode("\n", $problems)), true, now()->addHour())) {
            return;
        }

        try {
            Mail::to($recipient)->send(new HealthCheckAlert($problems));
        } catch (Throwable $e) {
            Log::warning('Could not send health check alert email', ['error' => $e->getMessage()]);
        }
    }
}
