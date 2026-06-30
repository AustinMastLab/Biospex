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
    protected $signature = 'app:update-queries {operation? : The operation to run (wedigbio-phase-1, wedigbio-phase-2, wedigbio-phase-3, wedigbio-phase-4, wedigbio-phase-5, wedigbio-phase-6)}';

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

        $this->error('Unknown operation. Try: wedigbio-phase-1, wedigbio-phase-2, wedigbio-phase-3, wedigbio-phase-4, wedigbio-phase-5, wedigbio-phase-6');

        return self::FAILURE;
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
                 FROM wedigbio_events we
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
     * Phase 3: Create Reports mirror view for Biospex event reads
     *
     * Creates a non-destructive staging view that mirrors Reports events and
     * includes a transcription presence flag using external_event_id mapping.
     */
    private function wedigbioPhase3(): int
    {
        $this->info('Starting WeDigBio Phase 3: Create Reports mirror view...');

        try {
            $this->line('  - Creating or replacing view wedigbio_events_reports_v');
            $viewSql = <<<'SQL'
            CREATE OR REPLACE VIEW wedigbio_events_reports_v AS
            SELECT
              re.id,
              re.slug,
              re.name,
              re.starts_at AS start_date,
              re.ends_at AS end_date,
              re.is_live,
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
                 WHERE table_schema = DATABASE() AND table_name = ?',
                ['wedigbio_events_reports_v']
            );

            if (! $viewExists) {
                $this->error('❌ View wedigbio_events_reports_v was not created');

                return self::FAILURE;
            }
            $this->line('  ✓ View exists');

            $viewCountResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_events_reports_v');
            $reportsCountResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_report.events');
            $viewCount = $viewCountResult?->cnt ?? 0;
            $reportsCount = $reportsCountResult?->cnt ?? 0;

            if ((int) $viewCount !== (int) $reportsCount) {
                $this->warn("⚠️  View row count ({$viewCount}) differs from Reports events ({$reportsCount})");
            } else {
                $this->line("  ✓ View row count matches Reports events ({$viewCount})");
            }

            $this->info('✅ Phase 3 completed successfully');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 3 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Phase 4: Create Release A compatibility view for Biospex reads
     *
     * Narrows the reports mirror to events that are currently live or have
     * transcriptions, matching the Release A application behavior.
     */
    private function wedigbioPhase4(): int
    {
        $this->info('Starting WeDigBio Phase 4: Create Release A compatibility view...');

        try {
            $reportsViewExists = DB::selectOne(
                'SELECT 1 AS exists_flag
                 FROM information_schema.views
                 WHERE table_schema = DATABASE() AND table_name = ?',
                ['wedigbio_events_reports_v']
            );

            if (! $reportsViewExists) {
                $this->error('❌ Missing prerequisite view wedigbio_events_reports_v. Run wedigbio-phase-3 first.');

                return self::FAILURE;
            }

            $this->line('  - Creating or replacing view wedigbio_events_release_a_v');
            $viewSql = <<<'SQL'
            CREATE OR REPLACE VIEW wedigbio_events_release_a_v AS
            SELECT
              rv.id,
              rv.slug,
              rv.name,
              rv.start_date,
              rv.end_date,
              rv.is_live,
              rv.active,
              rv.is_public,
              rv.is_archived,
              rv.display_alias,
              rv.year,
              rv.season,
              rv.created_at,
              rv.updated_at,
              rv.has_transcriptions
            FROM wedigbio_events_reports_v rv
            WHERE rv.is_live = 1
               OR rv.has_transcriptions = 1
            SQL;
            DB::statement($viewSql);

            $this->info('Running validation checks...');

            $releaseAExists = DB::selectOne(
                'SELECT 1 AS exists_flag
                 FROM information_schema.views
                 WHERE table_schema = DATABASE() AND table_name = ?',
                ['wedigbio_events_release_a_v']
            );

            if (! $releaseAExists) {
                $this->error('❌ View wedigbio_events_release_a_v was not created');

                return self::FAILURE;
            }
            $this->line('  ✓ View exists');

            $nullSlugResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_events_release_a_v WHERE slug IS NULL OR slug = ""');
            $nullSlugCount = $nullSlugResult?->cnt ?? 0;
            if ($nullSlugCount > 0) {
                $this->error("❌ Found {$nullSlugCount} rows without slug in wedigbio_events_release_a_v");

                return self::FAILURE;
            }
            $this->line('  ✓ All rows have slug values');

            $countResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_events_release_a_v');
            $count = $countResult?->cnt ?? 0;
            $this->line("  ✓ View returns {$count} events");

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

        // Confirmation guard
        if (! $this->confirm('Continue with Phase 5 cutover?', false)) {
            $this->info('Phase 5 cancelled.');

            return self::FAILURE;
        }

        try {
            // Pre-flight checks
            $this->line('  - Running pre-flight validation...');
            $unmappedResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_event_transcriptions WHERE external_event_id IS NULL');
            $unmappedCount = $unmappedResult?->cnt ?? 0;
            if ($unmappedCount > 0) {
                $this->error("❌ Found {$unmappedCount} transcriptions with null external_event_id. Cannot proceed.");

                return self::FAILURE;
            } else {
                $this->line('    ✓ All transcriptions mapped via external_event_id');
            }

            // Step 1: Drop old FK
            $this->line('  - Dropping old foreign key constraint');
            try {
                DB::statement('ALTER TABLE wedigbio_event_transcriptions DROP FOREIGN KEY wedigbio_event_transcriptions_date_id_foreign');
            } catch (Throwable $e) {
                $this->warn("    Note: FK constraint not found or already dropped: {$e->getMessage()}");
            }

            // Step 2: Drop old event_id column
            $this->line('  - Dropping old event_id column');
            DB::statement('ALTER TABLE wedigbio_event_transcriptions DROP COLUMN event_id');

            // Step 3: Rename external_event_id to event_id
            $this->line('  - Renaming external_event_id to event_id');
            DB::statement('ALTER TABLE wedigbio_event_transcriptions CHANGE COLUMN external_event_id event_id BIGINT UNSIGNED NOT NULL');

            // Step 4: Create index
            $this->line('  - Creating index on event_id');
            $this->ensureIndexExists(
                'wedigbio_event_transcriptions',
                'idx_wet_event_id',
                'CREATE INDEX idx_wet_event_id ON wedigbio_event_transcriptions (event_id)'
            );

            // Step 5: Attempt cross-db FK (optional, may fail)
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
                $this->warn("    ⚠️  Cross-db FK not supported: {$e->getMessage()}");
                $this->line('       App must enforce integrity via validation');
            }

            // Post-cutover validation
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
            } else {
                $this->line('  ✓ No orphaned transcriptions');
            }

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

        // Confirmation guard
        if (! $this->confirm('Continue with Phase 6 (table → view replacement)?', false)) {
            $this->info('Phase 6 cancelled.');

            return self::FAILURE;
        }

        try {
            // Step 1: Check if legacy table already exists
            $legacyExists = DB::selectOne(
                'SELECT 1 AS exists_flag FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?',
                ['wedigbio_events_legacy']
            );

            if ($legacyExists) {
                $this->warn('  - wedigbio_events_legacy already exists (table may have been previously replaced)');
            } else {
                $this->line('  - Renaming physical table to wedigbio_events_legacy');
                DB::statement('RENAME TABLE wedigbio_events TO wedigbio_events_legacy');
            }

            // Step 2: Create the view
            $this->line('  - Creating view biospex.wedigbio_events from Reports');
            $viewSql = <<<'SQL'
            CREATE OR REPLACE VIEW wedigbio_events AS
            SELECT
              re.id,
              re.slug,
              re.name,
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
            WHERE re.is_live = 1
               OR EXISTS (
                 SELECT 1
                 FROM wedigbio_event_transcriptions wet
                 WHERE wet.event_id = re.id
               )
            SQL;

            DB::statement($viewSql);

            // Post-replacement validation
            $this->info('Running post-replacement validation...');
            $viewExists = DB::selectOne(
                'SELECT 1 AS exists_flag FROM information_schema.views
                 WHERE table_schema = DATABASE() AND table_name = ?',
                ['wedigbio_events']
            );
            if (! $viewExists) {
                $this->error('❌ View was not created successfully');

                return self::FAILURE;
            } else {
                $this->line('  ✓ View created successfully');
            }

            // Check view returns data
            $viewResult = DB::selectOne('SELECT COUNT(*) AS cnt FROM wedigbio_events');
            $viewCount = $viewResult?->cnt ?? 0;
            $this->line("  ✓ View returns {$viewCount} events");

            $this->info('✅ Phase 6 completed successfully');
            $this->info('Legacy table archived as wedigbio_events_legacy for rollback safety.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Phase 6 failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
