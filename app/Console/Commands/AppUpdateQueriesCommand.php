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
use MongoDB\Collection;
use MongoDB\Driver\Exception\CommandException;

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
                            {operation? : The operation to run (pusher-transcription-duplicates)}
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
            'pusher-transcription-duplicates' => $this->pusherTranscriptionDuplicates(),
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
     * Remove duplicate pusher_transcriptions and make classification_id unique.
     *
     * Two Panoptes listeners and retried jobs saved some classifications many
     * times. For each classification_id the earliest document is kept and the
     * rest are deleted; then the classification_id index is rebuilt as unique so
     * PusherTranscriptionJob's duplicate-key handling stops any further copies.
     * Classifications can still arrive while this runs, so the cleanup repeats
     * if the unique index cannot be built yet. Safe to re-run.
     */
    private function pusherTranscriptionDuplicates(): int
    {
        $collection = DB::connection('mongodb')->getCollection('pusher_transcriptions');

        if ($this->hasUniqueClassificationIndex($collection)) {
            $this->info('pusher_transcriptions.classification_id is already unique; nothing to do.');

            return self::SUCCESS;
        }

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $deleted = $this->deleteDuplicateClassifications($collection);
            $this->info("Attempt {$attempt}: deleted {$deleted} duplicate pusher_transcriptions.");

            try {
                $this->replaceClassificationIndexWithUnique($collection);
                $this->info('pusher_transcriptions.classification_id is now unique.');

                return self::SUCCESS;
            } catch (CommandException $exception) {
                if (! str_contains($exception->getMessage(), 'E11000')) {
                    throw $exception;
                }

                $this->warn('New duplicates arrived before the unique index was built; cleaning up again.');
            }
        }

        $this->error('Could not build the unique classification_id index after 3 attempts.');

        return self::FAILURE;
    }

    /**
     * Delete every document but the earliest (by timestamp, then _id) for each classification_id.
     */
    private function deleteDuplicateClassifications(Collection $collection): int
    {
        $duplicates = $collection->aggregate([
            ['$sort' => ['classification_id' => 1, 'timestamp' => 1, '_id' => 1]],
            ['$group' => ['_id' => '$classification_id', 'ids' => ['$push' => '$_id'], 'count' => ['$sum' => 1]]],
            ['$match' => ['count' => ['$gt' => 1]]],
            ['$project' => ['extra' => ['$slice' => ['$ids', 1, ['$subtract' => ['$count', 1]]]]]],
        ], ['allowDiskUse' => true]);

        $deleted = 0;
        $batch = [];

        foreach ($duplicates as $duplicate) {
            foreach ($duplicate['extra'] as $id) {
                $batch[] = $id;
            }

            if (count($batch) >= 1000) {
                $deleted += $collection->deleteMany(['_id' => ['$in' => $batch]])->getDeletedCount();
                $batch = [];
            }
        }

        if ($batch !== []) {
            $deleted += $collection->deleteMany(['_id' => ['$in' => $batch]])->getDeletedCount();
        }

        return $deleted;
    }

    /**
     * Swap the non-unique classification_id index for a unique one.
     *
     * MongoDB does not allow two indexes on the same key with different
     * options, so the old index is dropped first. If the unique build fails
     * on a new duplicate, the plain index is restored before rethrowing so
     * the collection is never left without a classification_id index.
     */
    private function replaceClassificationIndexWithUnique(Collection $collection): void
    {
        foreach ($collection->listIndexes() as $index) {
            if ($index->getKey() === ['classification_id' => 1]) {
                $collection->dropIndex($index->getName());
            }
        }

        try {
            $collection->createIndex(['classification_id' => 1], ['unique' => true, 'name' => 'classification_id_1']);
        } catch (CommandException $exception) {
            $collection->createIndex(['classification_id' => 1], ['name' => 'classification_id_1']);

            throw $exception;
        }
    }

    private function hasUniqueClassificationIndex(Collection $collection): bool
    {
        foreach ($collection->listIndexes() as $index) {
            if ($index->getKey() === ['classification_id' => 1] && $index->isUnique()) {
                return true;
            }
        }

        return false;
    }
}
