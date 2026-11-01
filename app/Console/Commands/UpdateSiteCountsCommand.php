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

class UpdateSiteCountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-site-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Count the contributors and transcriptions shown on the home page and store them, so page requests never run the slow counts.';

    /**
     * Execute the console command.
     */
    public function handle(PanoptesTranscriptionService $panoptesTranscriptionService): int
    {
        $panoptesTranscriptionService->refreshSiteCounts();

        $this->info(sprintf(
            'Stored %d contributors and %d transcriptions.',
            $panoptesTranscriptionService->getContributorCount(),
            $panoptesTranscriptionService->getTotalTranscriptions(),
        ));

        return self::SUCCESS;
    }
}
