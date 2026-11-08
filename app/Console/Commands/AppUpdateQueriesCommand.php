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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off data updates run during deployment.
 *
 * Add an operation here when a release needs to change stored values, set
 * `update_queries_operation` in deploy.php, and remove the operation once it
 * has run in production.
 */
class AppUpdateQueriesCommand extends Command
{
    /**
     * The console command name.
     */
    protected $signature = 'app:update-queries
                            {operation? : The operation to run (unlock-expeditions)}
                            {--force : Run without confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Used for custom queries when updating database';

    /**
     * Run the requested operation.
     */
    public function handle(): int
    {
        $operation = (string) $this->argument('operation');

        return match ($operation) {
            '' => $this->noOperation(),
            'unlock-expeditions' => $this->unlockExpeditions(),
            default => $this->unknownOperation($operation),
        };
    }

    private function noOperation(): int
    {
        $this->info('No update operation given; nothing to do.');

        return self::SUCCESS;
    }

    private function unknownOperation(string $operation): int
    {
        $this->error("Unknown operation: {$operation}");

        return self::FAILURE;
    }

    /**
     * Unlock expeditions that were locked without changing to GeoLocate.
     *
     * The create, clone and edit forms used to lock an expedition on every save, so its workflow could no
     * longer be changed to GeoLocate. An expedition now locks only when it changes to the GeoLocate workflow,
     * so those stay locked. updated_at is left alone. Safe to re-run.
     */
    private function unlockExpeditions(): int
    {
        $unlocked = DB::table('expeditions')
            ->where('locked', 1)
            ->where('workflow_id', '!=', (int) config('geolocate.workflow_id'))
            ->update(['locked' => 0]);

        $this->info("Unlocked {$unlocked} expeditions.");

        return self::SUCCESS;
    }
}
