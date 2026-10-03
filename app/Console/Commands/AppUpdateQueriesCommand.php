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

use App\Models\Expedition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
                            {operation? : The operation to run (expedition-logo-paths)}
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
            'expedition-logo-paths' => $this->expeditionLogoPaths(),
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
     * Move expedition logos from the original/medium variant layout to a single file.
     *
     * For each expedition whose logo_path points into logos/original/, copy the
     * medium variant to logos/<file> and point logo_path at it. Expeditions whose
     * medium file is missing are reported and left unchanged. Safe to re-run.
     */
    private function expeditionLogoPaths(): int
    {
        $disk = Storage::disk('s3');
        $updated = 0;
        $copied = 0;
        $missing = 0;

        Expedition::query()
            ->where('logo_path', 'like', '%/logos/original/%')
            ->select(['id', 'logo_path'])
            ->chunkById(100, function ($expeditions) use ($disk, &$updated, &$copied, &$missing) {
                foreach ($expeditions as $expedition) {
                    $newPath = str_replace('/logos/original/', '/logos/', $expedition->logo_path);
                    $mediumPath = str_replace('/logos/original/', '/logos/medium/', $expedition->logo_path);

                    if (! $disk->exists($newPath)) {
                        if (! $disk->exists($mediumPath)) {
                            $this->warn("Expedition {$expedition->id}: medium logo not found at {$mediumPath}");
                            $missing++;

                            continue;
                        }

                        $disk->copy($mediumPath, $newPath);
                        $copied++;
                    }

                    Expedition::whereKey($expedition->id)->toBase()->update(['logo_path' => $newPath]);
                    $updated++;
                }
            });

        $this->info("Expedition logos: {$updated} paths updated, {$copied} files copied, {$missing} missing.");

        return self::SUCCESS;
    }
}
