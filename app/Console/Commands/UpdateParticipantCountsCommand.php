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

use App\Services\Transcriptions\PanoptesTranscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateParticipantCountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-participant-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Count each project\'s participants again for the statistics pages, which otherwise keep the counts from the last deploy.';

    /**
     * Execute the console command.
     */
    public function handle(PanoptesTranscriptionService $panoptesTranscriptionService): int
    {
        $failed = 0;
        $projectIds = $panoptesTranscriptionService->getTranscribedProjectIds();

        foreach ($projectIds as $projectId) {
            try {
                $panoptesTranscriptionService->refreshProjectParticipants($projectId);
            } catch (Throwable $exception) {
                // Keep going: the project keeps its previously stored counts.
                $failed++;
                Log::error('Participant counts failed for project '.$projectId.': '.$exception->getMessage());
                $this->error(sprintf('Project %d: %s', $projectId, $exception->getMessage()));
            }
        }

        $this->info(sprintf('Updated participant counts for %d of %d projects.', count($projectIds) - $failed, count($projectIds)));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
