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
                            {operation? : The operation to run}
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
}
