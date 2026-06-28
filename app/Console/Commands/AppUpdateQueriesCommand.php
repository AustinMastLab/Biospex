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
    protected $signature = 'app:update-queries {operation? : The operation to run (add-project-indexes, add-expedition-indexes, wedigbio-phase-1, wedigbio-phase-2, wedigbio-phase-3, wedigbio-phase-4, wedigbio-phase-5, wedigbio-phase-6)}';

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

        if ($operation === 'wedigbio-phase-1') {
            return $this->wedigbioPhase1();
        }

        if ($operation === 'wedigbio-phase-2') {
            return $this->wedigbioPhase2();
        }

        if ($operation === 'wedigbio-phase-3') {
            return $this->wedigbioPhase3();
        }

        if ($operation === 'wedigbio-phase-4') {
            return $this->wedigbioPhase4();
        }

        if ($operation === 'wedigbio-phase-5') {
            return $this->wedigbioPhase5();
        }

        if ($operation === 'wedigbio-phase-6') {
            return $this->wedigbioPhase6();
        }

        $this->error('Unknown operation. Try: add-project-indexes, add-expedition-indexes, wedigbio-phase-1, wedigbio-phase-2, wedigbio-phase-3, wedigbio-phase-4, wedigbio-phase-5, wedigbio-phase-6');

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
     * Phase 1: Add and populate external_event_id on biospex.wedigbio_events
     *
     * Adds the mapping column and populates 6 Biospex events with their Reports IDs.
     * Manual mapping: 1→11, 2→13, 3→14, 4→15, 5→21, 6→16
     */
    private function wedigbioPhase1(): int
    {
        $this->info('Starting WeDigBio Phase 1: Add event mapping column...');

        try {
            // Check if column already exists
            $columnExists = DB::selectOne(
                'SELECT 1 AS exists_flag
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                   AND column_name = ?',
                ['wedigbio_events', 'external_event_id']
            );

            if (! $columnExists) {
                $this->line('  - Adding external_event_id column to wedigbio_events');
                DB::statement('ALTER TABLE wedigbio_events ADD COLUMN external_event_id BIGINT UNSIGNED NULL AFTER id');
            } else {
                $this->line('  - external_event_id column already exists');
            }

            // Populate mappings (idempotent - only updates where null)
            $mappings = [
                1 => 11,
                2 => 13,
                3 => 14,
                4 => 15,
                5 => 21,
                6 => 16,
            ];

            foreach ($mappings as $biospexId => $reportsId) {
                $this->line("  - Mapping Biospex event {$biospexId} → Reports event {$reportsId}");
                DB::statement(
                    'UPDATE wedigbio_events SET external_event_id = ? WHERE id = ? AND external_event_id IS NULL',
                    [$reportsId, $biospexId]
                );
            }

            // Validation checks
            $this->info('Running validation checks...');

            // Check 1: No nulls in mapped events
            $nullResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_events WHERE external_event_id IS NULL');
            $nullCount = $nullResult?->cnt ?? 0;
            if ($nullCount > 0) {
                $this->warn("⚠️  {$nullCount} events still have null external_event_id (may be unmapped Biospex events)");
            } else {
                $this->line('  ✓ All events have external_event_id populated');
            }

            // Check 2: No duplicates
            $duplicateResult = DB::selectOne(
                'SELECT COUNT(*) AS cnt FROM wedigbio_events WHERE external_event_id IS NOT NULL GROUP BY external_event_id HAVING COUNT(*) > 1'
            );
            $duplicateCount = $duplicateResult?->cnt ?? 0;
            if ($duplicateCount > 0) {
                $this->error('❌ Found duplicate external_event_id values');

                return self::FAILURE;
            } else {
                $this->line('  ✓ No duplicate external_event_id values');
            }

            // Check 3: All mapped IDs exist in Reports events
            $orphanResult = DB::selectOne(
                'SELECT COUNT(*) AS cnt
                 FROM biospex.wedigbio_events we
                 LEFT JOIN wedigbio_report.events re ON re.id = we.external_event_id
                 WHERE we.external_event_id IS NOT NULL AND re.id IS NULL'
            );
            $orphanCount = $orphanResult?->cnt ?? 0;
            if ($orphanCount > 0) {
                $this->error("❌ Found {$orphanCount} events with invalid Reports IDs");

                return self::FAILURE;
            } else {
                $this->line('  ✓ All mapped Reports IDs exist');
            }

            $this->info('✅ Phase 1 completed successfully');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 1 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Phase 2: Add and backfill external_event_id on biospex.wedigbio_event_transcriptions
     *
     * Adds the staging column and backfills from mapped Biospex events.
     * This enables the transition phase where both old and new references coexist.
     */
    private function wedigbioPhase2(): int
    {
        $this->info('Starting WeDigBio Phase 2: Stage transcription external_event_id...');

        try {
            // Check if column already exists
            $columnExists = DB::selectOne(
                'SELECT 1 AS exists_flag
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                   AND column_name = ?',
                ['wedigbio_event_transcriptions', 'external_event_id']
            );

            if (! $columnExists) {
                $this->line('  - Adding external_event_id column to wedigbio_event_transcriptions');
                DB::statement('ALTER TABLE wedigbio_event_transcriptions ADD COLUMN external_event_id BIGINT UNSIGNED NULL AFTER event_id');

                $this->line('  - Creating index on external_event_id');
                $this->ensureIndexExists(
                    'wedigbio_event_transcriptions',
                    'idx_wet_external_event_id',
                    'CREATE INDEX idx_wet_external_event_id ON wedigbio_event_transcriptions (external_event_id)'
                );
            } else {
                $this->line('  - external_event_id column already exists');
            }

            // Backfill from Phase 1 mapping
            $this->line('  - Backfilling external_event_id from event mappings');
            DB::statement(
                'UPDATE wedigbio_event_transcriptions wet
                 JOIN wedigbio_events we ON we.id = wet.event_id
                 SET wet.external_event_id = we.external_event_id
                 WHERE wet.external_event_id IS NULL AND we.external_event_id IS NOT NULL'
            );

            // Validation checks
            $this->info('Running validation checks...');

            // Check 1: 100% coverage - no nulls
            $unmappedResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_event_transcriptions WHERE external_event_id IS NULL');
            $unmappedCount = $unmappedResult?->cnt ?? 0;
            if ($unmappedCount > 0) {
                $this->warn("⚠️  {$unmappedCount} transcriptions could not be mapped (may indicate unmapped Biospex events)");
                $this->line('  - Listing unmapped transcriptions:');
                $unmapped = DB::select(
                    'SELECT id, event_id FROM wedigbio_event_transcriptions WHERE external_event_id IS NULL LIMIT 10'
                );
                foreach ($unmapped as $row) {
                    $this->line("    - Transcription {$row->id} linked to event {$row->event_id}");
                }
            } else {
                $this->line('  ✓ All transcriptions have external_event_id populated (100% coverage)');
            }

            // Check 2: All mapped IDs exist in Reports events
            $orphanResult = DB::selectOne(
                'SELECT COUNT(*) AS cnt
                 FROM wedigbio_event_transcriptions wet
                 LEFT JOIN wedigbio_report.events re ON re.id = wet.external_event_id
                 WHERE wet.external_event_id IS NOT NULL AND re.id IS NULL'
            );
            $orphanCount = $orphanResult?->cnt ?? 0;
            if ($orphanCount > 0) {
                $this->error("❌ Found {$orphanCount} transcriptions with invalid Reports event IDs");

                return self::FAILURE;
            } else {
                $this->line('  ✓ All mapped Reports event IDs exist');
            }

            $this->info('✅ Phase 2 completed successfully');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 2 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Phase 3: Create Reports-backed compatibility view.
     *
     * Creates a read-only compatibility view with event fields needed for
     * upcoming code cutover while preserving the physical wedigbio_events table.
     */
    private function wedigbioPhase3(): int
    {
        $this->info('Starting WeDigBio Phase 3: Create Reports-backed compatibility view...');

        try {
            $this->line('  - Creating or replacing view wedigbio_events_reports_v');
            $viewSql = <<<'SQL'
            CREATE OR REPLACE VIEW wedigbio_events_reports_v AS
            SELECT
              re.id AS reports_event_id,
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
              EXISTS (
                SELECT 1
                FROM wedigbio_event_transcriptions wet
                WHERE wet.external_event_id = re.id
              ) AS has_transcriptions
            FROM wedigbio_report.events re
            SQL;

            DB::statement($viewSql);

            $this->info('Running validation checks...');

            $viewExists = DB::selectOne(
                'SELECT 1 AS exists_flag
                 FROM information_schema.views
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events_reports_v']
            );

            if (! $viewExists) {
                $this->error('❌ View wedigbio_events_reports_v was not created.');

                return self::FAILURE;
            }

            $viewCountResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_events_reports_v');
            $viewCount = $viewCountResult?->cnt ?? 0;
            $this->line("  ✓ View exists and returns {$viewCount} rows");

            $this->info('✅ Phase 3 completed successfully');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 3 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Phase 4: Create Release A read view consumed by Biospex code.
     *
     * Keeps legacy table for writes, while read-paths switch to Reports IDs/slugs.
     */
    private function wedigbioPhase4(): int
    {
        $this->info('Starting WeDigBio Phase 4: Create Release A read view...');

        try {
            $this->line('  - Creating or replacing view wedigbio_events_release_a_v');
            $viewSql = <<<'SQL'
            CREATE OR REPLACE VIEW wedigbio_events_release_a_v AS
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
              we.id AS legacy_event_id,
              COALESCE(we.uuid, re.slug) AS channel_key,
              EXISTS (
                SELECT 1
                FROM wedigbio_event_transcriptions wet
                WHERE wet.external_event_id = re.id
              ) AS has_transcriptions
            FROM wedigbio_report.events re
            LEFT JOIN biospex.wedigbio_events we ON we.external_event_id = re.id
            SQL;

            DB::statement($viewSql);

            $this->info('Running validation checks...');

            $viewExists = DB::selectOne(
                'SELECT 1 AS exists_flag
                 FROM information_schema.views
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events_release_a_v']
            );

            if (! $viewExists) {
                $this->error('❌ View wedigbio_events_release_a_v was not created.');

                return self::FAILURE;
            }

            $counts = DB::selectOne(
                'SELECT
                   (SELECT COUNT(*) FROM wedigbio_report.events) AS reports_cnt,
                   (SELECT COUNT(*) FROM wedigbio_events_release_a_v) AS view_cnt'
            );

            $reportsCount = $counts?->reports_cnt ?? 0;
            $viewCount = $counts?->view_cnt ?? 0;

            if ((int) $reportsCount !== (int) $viewCount) {
                $this->error("❌ View row count mismatch. reports={$reportsCount}, view={$viewCount}");

                return self::FAILURE;
            }

            $this->line("  ✓ View exists and mirrors {$viewCount} Reports events");
            $this->info('✅ Phase 4 completed successfully');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 4 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Phase 5: Final cutover - move from external_event_id to Reports-backed event_id
     *
     * Destructive operation (run in maintenance window).
     * Drops old event_id column, renames external_event_id to event_id.
     */
    private function wedigbioPhase5(): int
    {
        $this->info('Starting WeDigBio Phase 5: Final cutover of event_id...');
        $this->warn('⚠️  This is a destructive operation. Ensure backup is taken and app is in maintenance mode.');

        // In interactive runs ask for confirmation; in deploy (non-interactive) continue.
        if ($this->input->isInteractive() && ! $this->confirm('Continue with Phase 5 cutover?', false)) {
            $this->info('Phase 5 cancelled.');

            return self::FAILURE;
        }

        try {
            $columns = DB::select(
                'SELECT column_name AS col_name
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = ?',
                ['wedigbio_event_transcriptions']
            );
            $columnNames = collect($columns)->pluck('col_name')->all();
            $hasLegacyEventId = in_array('event_id', $columnNames, true);
            $hasExternalEventId = in_array('external_event_id', $columnNames, true);

            // Already cut over: event_id exists and external_event_id is gone.
            if ($hasLegacyEventId && ! $hasExternalEventId) {
                $this->line('  - Phase 5 appears already applied (event_id present, external_event_id absent).');
            } else {
                // Pre-flight checks while external_event_id still exists.
                $this->line('  - Running pre-flight validation...');
                $unmappedResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_event_transcriptions WHERE external_event_id IS NULL');
                $unmappedCount = $unmappedResult?->cnt ?? 0;
                if ($unmappedCount > 0) {
                    $this->error("❌ Found {$unmappedCount} transcriptions with null external_event_id. Cannot proceed.");

                    return self::FAILURE;
                }
                $this->line('    ✓ All transcriptions mapped via external_event_id');

                // Drop any FK that still ties event_id to legacy biospex.wedigbio_events.
                $this->line('  - Dropping legacy foreign key constraints on event_id (if present)');
                $legacyFks = DB::select(
                    'SELECT constraint_name AS fk_name
                     FROM information_schema.key_column_usage
                     WHERE table_schema = DATABASE()
                       AND table_name = ?
                       AND column_name = ?
                       AND referenced_table_name = ?',
                    ['wedigbio_event_transcriptions', 'event_id', 'wedigbio_events']
                );
                foreach ($legacyFks as $fk) {
                    DB::statement("ALTER TABLE wedigbio_event_transcriptions DROP FOREIGN KEY {$fk->fk_name}");
                    $this->line("    - Dropped FK {$fk->fk_name}");
                }

                // Drop legacy event_id, then rename external_event_id -> event_id.
                if ($hasLegacyEventId) {
                    $this->line('  - Dropping legacy event_id column');
                    DB::statement('ALTER TABLE wedigbio_event_transcriptions DROP COLUMN event_id');
                }

                $this->line('  - Renaming external_event_id to event_id');
                DB::statement('ALTER TABLE wedigbio_event_transcriptions CHANGE COLUMN external_event_id event_id BIGINT UNSIGNED NOT NULL');

                $this->line('  - Rebuilding event_id index');
                DB::statement('DROP INDEX idx_wet_external_event_id ON wedigbio_event_transcriptions');
                $this->ensureIndexExists(
                    'wedigbio_event_transcriptions',
                    'idx_wet_event_id',
                    'CREATE INDEX idx_wet_event_id ON wedigbio_event_transcriptions (event_id)'
                );
            }

            // Keep Release A view valid after cutover (it must reference wet.event_id now).
            $this->line('  - Rebuilding view wedigbio_events_release_a_v for post-cutover schema');
            $releaseAViewSql = <<<'SQL'
            CREATE OR REPLACE VIEW wedigbio_events_release_a_v AS
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
              we.id AS legacy_event_id,
              COALESCE(we.uuid, re.slug) AS channel_key,
              EXISTS (
                SELECT 1
                FROM wedigbio_event_transcriptions wet
                WHERE wet.event_id = re.id
              ) AS has_transcriptions
            FROM wedigbio_report.events re
            LEFT JOIN biospex.wedigbio_events we ON we.external_event_id = re.id
            SQL;
            DB::statement($releaseAViewSql);

            // Optional cross-db FK.
            $this->line('  - Attempting to add cross-database foreign key (optional)...');
            try {
                DB::statement(
                    'ALTER TABLE wedigbio_event_transcriptions
                     ADD CONSTRAINT wet_event_id_reports_events_fk
                     FOREIGN KEY (event_id) REFERENCES wedigbio_report.events(id)
                     ON DELETE CASCADE
                     ON UPDATE NO ACTION'
                );
                $this->line('    ✓ Cross-database FK added successfully');
            } catch (Throwable $e) {
                $this->warn("    ⚠️  Cross-db FK not supported or already present: {$e->getMessage()}");
            }

            // Post-cutover validation.
            $this->info('Running post-cutover validation...');
            $orphanResult = DB::selectOne(
                'SELECT COUNT(*) AS cnt
                 FROM wedigbio_event_transcriptions wet
                 LEFT JOIN wedigbio_report.events re ON re.id = wet.event_id
                 WHERE re.id IS NULL'
            );
            $orphanCount = $orphanResult?->cnt ?? 0;
            if ($orphanCount > 0) {
                $this->error("❌ Found {$orphanCount} orphaned transcriptions");

                return self::FAILURE;
            }

            $this->line('  ✓ No orphaned transcriptions');
            $this->info('✅ Phase 5 completed successfully');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 5 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Phase 6: Replace physical table with Reports-backed view
     *
     * Final table replacement step (run after Phase 5 in maintenance window).
     * Renames old table to _legacy, creates view that materializes from Reports.
     */
    private function wedigbioPhase6(): int
    {
        $this->info('Starting WeDigBio Phase 6: Replace table with Reports-backed view...');
        $this->warn('⚠️  This operation replaces the physical table with a view.');

        // In interactive runs ask for confirmation; in deploy (non-interactive) continue.
        if ($this->input->isInteractive() && ! $this->confirm('Continue with Phase 6 (table → view replacement)?', false)) {
            $this->info('Phase 6 cancelled.');

            return self::FAILURE;
        }

        try {
            $currentObject = DB::selectOne(
                'SELECT table_type AS obj_type
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events']
            );

            $legacyExists = DB::selectOne(
                'SELECT 1 AS exists_flag
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events_legacy']
            );

            if ($currentObject && $currentObject->obj_type === 'BASE TABLE') {
                if ($legacyExists) {
                    $this->error('❌ Cannot rename wedigbio_events because wedigbio_events_legacy already exists.');

                    return self::FAILURE;
                }

                $this->line('  - Renaming physical table to wedigbio_events_legacy');
                DB::statement('RENAME TABLE wedigbio_events TO wedigbio_events_legacy');
                $legacyExists = (object) ['exists_flag' => 1];
            } elseif ($currentObject && $currentObject->obj_type === 'VIEW') {
                $this->line('  - wedigbio_events is already a view; refreshing definition');
            } else {
                $this->warn('  - wedigbio_events object not found; creating view from scratch');
            }

            // Build final view SQL; if legacy table exists, preserve uuid-derived channel_key continuity.
            $this->line('  - Creating view biospex.wedigbio_events from Reports');
            if ($legacyExists) {
                $viewSql = <<<'SQL'
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
                  wel.id AS legacy_event_id,
                  COALESCE(wel.uuid, re.slug) AS channel_key,
                  EXISTS (
                    SELECT 1
                    FROM wedigbio_event_transcriptions wet
                    WHERE wet.event_id = re.id
                  ) AS has_transcriptions
                FROM wedigbio_report.events re
                LEFT JOIN biospex.wedigbio_events_legacy wel ON wel.external_event_id = re.id
                WHERE re.is_live = 1
                   OR EXISTS (
                     SELECT 1
                     FROM wedigbio_event_transcriptions wet
                     WHERE wet.event_id = re.id
                   )
                SQL;
            } else {
                $viewSql = <<<'SQL'
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
                SQL;
            }

            DB::statement($viewSql);

            // Keep auxiliary compatibility views queryable after Phase 6.
            $this->line('  - Refreshing helper views wedigbio_events_reports_v and wedigbio_events_release_a_v');

            $reportsViewSql = <<<'SQL'
            CREATE OR REPLACE VIEW wedigbio_events_reports_v AS
            SELECT
              re.id AS reports_event_id,
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
              EXISTS (
                SELECT 1
                FROM wedigbio_event_transcriptions wet
                WHERE wet.event_id = re.id
              ) AS has_transcriptions
            FROM wedigbio_report.events re
            SQL;
            DB::statement($reportsViewSql);

            if ($legacyExists) {
                $releaseAViewSql = <<<'SQL'
                CREATE OR REPLACE VIEW wedigbio_events_release_a_v AS
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
                  wel.id AS legacy_event_id,
                  COALESCE(wel.uuid, re.slug) AS channel_key,
                  EXISTS (
                    SELECT 1
                    FROM wedigbio_event_transcriptions wet
                    WHERE wet.event_id = re.id
                  ) AS has_transcriptions
                FROM wedigbio_report.events re
                LEFT JOIN biospex.wedigbio_events_legacy wel ON wel.external_event_id = re.id
                SQL;
            } else {
                $releaseAViewSql = <<<'SQL'
                CREATE OR REPLACE VIEW wedigbio_events_release_a_v AS
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
                SQL;
            }
            DB::statement($releaseAViewSql);

            // Post-replacement validation
            $this->info('Running post-replacement validation...');
            $viewExists = DB::selectOne(
                'SELECT 1 AS exists_flag
                 FROM information_schema.views
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                 LIMIT 1',
                ['wedigbio_events']
            );
            if (! $viewExists) {
                $this->error('❌ View was not created successfully');

                return self::FAILURE;
            }

            $viewResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_events');
            $viewCount = $viewResult?->cnt ?? 0;
            $this->line("  ✓ View created successfully and returns {$viewCount} events");

            $this->info('✅ Phase 6 completed successfully');
            $this->info('Legacy table archived as wedigbio_events_legacy for rollback safety (if present).');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 6 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
