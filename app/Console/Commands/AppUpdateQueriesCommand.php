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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Class UpdateQueries
 */
class AppUpdateQueriesCommand extends Command
{
    /**
     * The console command name.
     */
    protected $signature = 'app:update-queries {operation? : The operation to run (create-directories, move-files, update-paths)}';

    /**
     * The console command description.
     */
    protected $description = 'Used for custom queries when updating database';

    /**
     * UpdateQueries constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Fire command
     */
    public function handle(): int
    {
        $operation = $this->argument('operation') ?? '';

        if ($operation === 'add-project-indexes') {
            return $this->addProjectIndexes();
        }

        $this->error('Unknown operation. Try: add-project-indexes');

        return self::FAILURE;
    }

    private function addProjectIndexes(): int
    {
        $this->info('Adding missing indexes for projects sorting...');

        try {
            $this->ensureIndexExists('projects', 'projects_title_index', 'CREATE INDEX projects_title_index ON projects (title)');
            $this->ensureIndexExists('projects', 'projects_created_at_index', 'CREATE INDEX projects_created_at_index ON projects (created_at)');

            // Optional: only if you decide you want it
            // $this->ensureIndexExists('projects', 'projects_group_id_title_index', 'CREATE INDEX projects_group_id_title_index ON projects (group_id, title)');

            $this->info('Done.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function ensureIndexExists(string $table, string $indexName, string $createSql): void
    {
        $exists = DB::selectOne(
            'SELECT 1 AS exists_flag
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND index_name = ?
             LIMIT 1',
            [$table, $indexName]
        );

        if ($exists) {
            $this->line("  - {$indexName} already exists on {$table}");

            return;
        }

        $this->line("  - creating {$indexName} on {$table}");
        DB::statement($createSql);
    }
}
