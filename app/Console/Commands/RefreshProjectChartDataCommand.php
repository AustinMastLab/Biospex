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

use App\Models\Project;
use App\Services\Chart\ProjectTranscriptionChart;
use Illuminate\Console\Command;
use Spatie\ResponseCache\Facades\ResponseCache;

class RefreshProjectChartDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-project-chart-data {projectIds?* : Projects to refresh; all projects with expeditions on Zooniverse when left out} {--full : Count every day again, not just from the last stored day}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Count each project\'s transcriptions per expedition per day for its Digitizations chart.';

    /**
     * Execute the console command.
     */
    public function handle(ProjectTranscriptionChart $chart): int
    {
        $projects = Project::query()
            ->when($this->argument('projectIds') !== [], fn ($query) => $query->whereIn('id', $this->argument('projectIds')))
            ->whereHas('expeditions.panoptesProject')
            ->get(['id', 'title']);

        foreach ($projects as $project) {
            $days = $chart->refresh($project, (bool) $this->option('full'));
            $this->line(sprintf('Project %d (%s): %d days stored.', $project->id, $project->title, $days));
        }

        // The project pages show the new counts.
        if (config('responsecache.enabled')) {
            ResponseCache::clear();
        }

        $this->info(sprintf('Refreshed chart data for %d projects.', $projects->count()));

        return self::SUCCESS;
    }
}
