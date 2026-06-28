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
    protected $signature = 'app:update-queries {operation? : The operation to run (add-project-indexes, add-expedition-indexes, wedigbio-phase-7, wedigbio-phase-8)}';

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

        if ($operation === 'add-expedition-indexes') {
            return $this->addExpeditionIndexes();
        }

        if ($operation === 'wedigbio-phase-7') {
            return $this->wedigbioPhase7();
        }

        if ($operation === 'wedigbio-phase-8') {
            return $this->wedigbioPhase8();
        }

        $this->error('Unknown operation. Try: add-project-indexes, add-expedition-indexes, wedigbio-phase-7, wedigbio-phase-8');

        return self::FAILURE;
    }

    private function addExpeditionIndexes(): int
    {
        $this->info('Adding missing indexes for expeditions sorting...');

        try {
            // Must-have (sorting)
            $this->ensureIndexExists('expeditions', 'expeditions_title_index', 'CREATE INDEX expeditions_title_index ON expeditions (title)');
            $this->ensureIndexExists('expeditions', 'expeditions_created_at_index', 'CREATE INDEX expeditions_created_at_index ON expeditions (created_at)');

            // Nice-to-have for project-scoped lists
            $this->ensureIndexExists('expeditions', 'expeditions_project_id_created_at_index', 'CREATE INDEX expeditions_project_id_created_at_index ON expeditions (project_id, created_at)');
            $this->ensureIndexExists('expeditions', 'expeditions_project_id_title_index', 'CREATE INDEX expeditions_project_id_title_index ON expeditions (project_id, title)');

            // Pivot: enforce uniqueness + speed up "has Zooniverse actor" checks
            $this->ensureIndexExists(
                'actor_expedition',
                'actor_expedition_expedition_id_actor_id_unique',
                'CREATE UNIQUE INDEX actor_expedition_expedition_id_actor_id_unique ON actor_expedition (expedition_id, actor_id)'
            );

            $this->info('Done.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }
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

    /**
     * Phase 7: Remove legacy/transitional WeDigBio objects after cutover grace period.
     */
    private function wedigbioPhase7(): int
    {
        $this->info('Starting WeDigBio Phase 7: Cleanup legacy/transitional DB objects...');
        $this->warn('⚠️  This removes rollback/archive objects. Run only after cutover is verified in the target environment.');

        try {
            $cleanupStatements = [
                <<<'SQL'
                CREATE OR REPLACE VIEW wedigbio_events AS
                SELECT
                  re.id,
                  re.slug,
                  COALESCE(re.display_alias, CONCAT(re.year, ' ', re.season)) AS name,
                  re.starts_at AS start_date,
                  re.ends_at AS end_date,
                  re.is_live AS active,
                  re.is_public,
                  re.is_archived,
                  re.display_alias,
                  re.year,
                  re.season,
                  re.created_at,
                  re.updated_at,
                  NULL AS legacy_event_id,
                  re.slug AS channel_key,
                  EXISTS (
                    SELECT 1
                    FROM wedigbio_event_transcriptions wet
                    WHERE wet.event_id = re.id
                  ) AS has_transcriptions
                FROM wedigbio_report.events re
                WHERE re.is_live = 1
                   OR EXISTS (
                     SELECT 1
                     FROM wedigbio_event_transcriptions wet
                     WHERE wet.event_id = re.id
                   )
                SQL,
                'DROP VIEW IF EXISTS wedigbio_events_release_a_v',
                'DROP VIEW IF EXISTS wedigbio_events_reports_v',
                'DROP TABLE IF EXISTS wedigbio_events_legacy',
            ];

            foreach ($cleanupStatements as $statement) {
                DB::statement($statement);
            }

            $this->info('Running post-cleanup validation...');

            $eventsObject = DB::selectOne(
                'SELECT table_type AS obj_type
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events']
            );

            if (! $eventsObject || $eventsObject->obj_type !== 'VIEW') {
                $this->error('❌ wedigbio_events is not a view after Phase 7 cleanup.');

                return self::FAILURE;
            }

            $legacyObject = DB::selectOne(
                'SELECT 1 AS exists_flag
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events_legacy']
            );

            if ($legacyObject) {
                $this->error('❌ wedigbio_events_legacy still exists after cleanup.');

                return self::FAILURE;
            }

            $eventsCount = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_events');
            $this->line('  ✓ wedigbio_events view is present after cleanup');
            $this->line('  ✓ wedigbio_events_legacy removed');
            $this->line('  ✓ wedigbio_events returns '.($eventsCount?->cnt ?? 0).' rows');
            $this->info('✅ Phase 7 completed successfully');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 7 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Phase 8: Keep deploy pipeline in normal mode with post-cleanup validation.
     */
    private function wedigbioPhase8(): int
    {
        $this->info('Starting WeDigBio Phase 8: Post-cleanup steady-state validation...');

        try {
            $legacyObject = DB::selectOne(
                'SELECT table_name AS obj_name
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events_legacy']
            );

            $releaseAObject = DB::selectOne(
                'SELECT table_name AS obj_name
                 FROM information_schema.views
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events_release_a_v']
            );

            $reportsObject = DB::selectOne(
                'SELECT table_name AS obj_name
                 FROM information_schema.views
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events_reports_v']
            );

            if ($legacyObject || $releaseAObject || $reportsObject) {
                $this->warn('⚠️  Transitional/legacy objects still exist. Run wedigbio-phase-7 in this environment.');

                return self::SUCCESS;
            }

            $eventsObject = DB::selectOne(
                'SELECT table_type AS obj_type
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events']
            );

            if (! $eventsObject || $eventsObject->obj_type !== 'VIEW') {
                $this->error('❌ wedigbio_events should be a view in steady-state.');

                return self::FAILURE;
            }

            $count = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_events');
            $this->line('  ✓ Steady-state objects validated');
            $this->line('  ✓ wedigbio_events rows: '.($count?->cnt ?? 0));
            $this->info('✅ Phase 8 completed successfully');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 8 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
