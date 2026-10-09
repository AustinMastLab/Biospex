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

namespace App\Services;

use fXmlRpc\Transport\PsrTransport;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Supervisor\Supervisor;

/**
 * Service for controlling Supervisor processes.
 *
 * This service provides methods to manage Supervisor processes, including starting,
 * stopping, and restarting programs within specified groups.
 */
class SupervisorControlService
{
    /**
     * Control Supervisor programs with a specified action.
     *
     * @param  array  $programs  List of program names to control
     * @param  string  $action  Action to perform ('start', 'stop', or 'restart')
     *
     * @throws \InvalidArgumentException When an invalid action is provided
     */
    public function control(array $programs, string $action): void
    {
        $group = config('app.tag', ''); // e.g. "biospex" or "listeners"

        $supervisor = $this->supervisor();

        foreach ($programs as $program) {
            // Add group prefix if set
            $fullProgramName = $group ? "{$group}:{$program}" : $program;

            try {
                $info = $supervisor->getProcessInfo($fullProgramName);
                $state = $info['statename'] ?? 'UNKNOWN';
            } catch (\Throwable $e) {
                // Program might not exist yet — treat as STOPPED
                $state = 'STOPPED';
                \Log::info("Supervisor: Program {$fullProgramName} not found in Supervisor — assuming STOPPED");
            }

            match ($action) {
                'start' => $this->startIfNeeded($supervisor, $fullProgramName, $state),
                'stop' => $this->stopIfNeeded($supervisor, $fullProgramName, $state),
                'restart' => $this->restartProcess($supervisor, $fullProgramName, $state),
                default => throw new \InvalidArgumentException("Invalid action: {$action}"),
            };

            \Log::info("Supervisor: {$action}ed program {$fullProgramName} (was {$state})");
        }
    }

    /**
     * State of every program in this app's Supervisor group, e.g. ['default_00' => 'RUNNING'].
     *
     * @return array<string, string>
     */
    public function processStates(): array
    {
        $group = config('app.tag', '');

        return collect($this->supervisor()->getAllProcessInfo())
            ->filter(fn (array $process) => $process['group'] === $group)
            ->mapWithKeys(fn (array $process) => [$process['name'] => $process['statename']])
            ->all();
    }

    /**
     * Supervisor XML-RPC client on the local Unix socket.
     */
    protected function supervisor(): Supervisor
    {
        $guzzle = new Client([
            'curl' => [CURLOPT_UNIX_SOCKET_PATH => '/var/run/supervisor.sock'],
        ]);

        $transport = new PsrTransport(new HttpFactory, $guzzle);

        return new Supervisor(new \fXmlRpc\Client('http://localhost/RPC2', $transport));
    }

    /**
     * Start a Supervisor program if it's not already running.
     *
     * @param  Supervisor  $supervisor  Supervisor instance
     * @param  string  $program  Program name
     * @param  string  $state  Current state of the program
     */
    private function startIfNeeded(Supervisor $supervisor, string $program, string $state): void
    {
        if (in_array($state, ['STOPPED', 'BACKOFF', 'EXITED', 'FATAL', 'UNKNOWN'])) {
            $supervisor->startProcess($program);
        } else {
            \Log::info("Supervisor: {$program} already running — skipping start");
        }
    }

    /**
     * Stop a Supervisor program if it's currently running.
     *
     * @param  Supervisor  $supervisor  Supervisor instance
     * @param  string  $program  Program name
     * @param  string  $state  Current state of the program
     */
    private function stopIfNeeded(Supervisor $supervisor, string $program, string $state): void
    {
        if ($state === 'RUNNING') {
            $supervisor->stopProcess($program);
        } else {
            \Log::info("Supervisor: {$program} not running — skipping stop");
        }
    }

    /**
     * Restart a Supervisor program regardless of its current state.
     *
     * @param  Supervisor  $supervisor  Supervisor instance
     * @param  string  $program  Program name
     * @param  string  $state  Current state of the program
     */
    private function restartProcess(Supervisor $supervisor, string $program, string $state): void
    {
        if ($state === 'RUNNING') {
            $supervisor->stopProcess($program);
        }
        // Always start — works whether it was running or not
        $supervisor->startProcess($program);
    }
}
