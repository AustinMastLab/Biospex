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

namespace App\Jobs;

use App\Enums\ActorExpeditionState;
use App\Models\ActorExpedition;
use App\Models\User;
use App\Notifications\Generic;
use App\Services\Actor\GeoLocate\GeoLocateStatService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Job class responsible for processing GeoLocate statistics for an actor expedition.
 * This includes managing community and data source statistics, updating relevant records,
 * dispatching file downloads, and notifying the user upon completion or failure.
 */
class GeoLocateStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Constructor method for initializing the ActorExpedition instance and setting up the queue.
     *
     * @param  ActorExpedition  $actorExpedition  The actor expedition instance to be processed.
     * @param  bool  $refresh  Flag indicating whether to refresh the expedition's data. Defaults to false.
     * @return void
     */
    public function __construct(protected ActorExpedition $actorExpedition, protected bool $refresh = false)
    {
        $this->actorExpedition = $actorExpedition->withoutRelations();
        $this->onQueue(config('config.queue.geolocate'));
    }

    /**
     * Handles the GeoLocate process, including fetching and updating data for GeoLocate communities and data sources.
     * It also dispatches a download job for KML and CSV files and notifies the user upon completion.
     *
     * @param  GeoLocateStatService  $geoLocateStatService  The service used for processing GeoLocate statistics and updates.
     *
     * @throws GuzzleException
     */
    public function handle(GeoLocateStatService $geoLocateStatService): void
    {
        $this->actorExpedition->load(['expedition.project.group.owner', 'expedition.geoLocateDataSource.geolocateCommunity']);

        $expedition = $this->actorExpedition->expedition;
        $geoLocateDataSource = $expedition->geoLocateDataSource;
        $geoLocateCommunity = $expedition->geoLocateDataSource->geoLocateCommunity;

        if (! $this->refresh && $geoLocateDataSource->updated_at->diffInDays(now()) < 2) {
            return;
        }

        // get community stats
        $communityStats = $geoLocateStatService->getCommunityDataSource($geoLocateCommunity->name);

        // get dataSource stats
        $dataSourceStats = $geoLocateStatService->getCommunityDataSource($geoLocateCommunity->name, $geoLocateDataSource->data_source);

        // update geo_locate_communities data
        $geoLocateStatService->updateGeoLocateCommunityStat($geoLocateCommunity->id, $communityStats);

        // update geo_locate_data_sources data
        $geoLocateStatService->updateGeoLocateDataSourceStat($geoLocateDataSource->id, $dataSourceStats);

        // Touch for updated_at
        $geoLocateDataSource->touch();

        // Dispatch download job for kml and csv file.
        GeoLocateDownloadJob::dispatch($this->actorExpedition, $geoLocateCommunity->name, $geoLocateDataSource->data_source);

        // If completed and notify user.
        if ($dataSourceStats['stats']['correctedLocalityRecords'] >= $dataSourceStats['stats']['localityRecords']) {
            $this->actorExpedition->state = ActorExpeditionState::Complete;
            $this->actorExpedition->save();

            $attributes = [
                'subject' => t('GeoLocate stats for %s is complete.', $expedition->title),
                'html' => [
                    t('The GeoLocate Stat process is complete and the KML file is ready for download.'),
                    t('You can download the file from the Downloads button of the Expedition.'),
                ],
            ];

            $expedition->project->group->owner->notify(new Generic($attributes));
        }
    }

    /**
     * Handles the failed execution of the job and sends a notification with error details.
     *
     * @param  Throwable  $throwable  The exception instance containing the error details.
     */
    public function failed(Throwable $throwable): void
    {
        $subject = t('GeoLocate stats for %s failed.', $this->actorExpedition->expedition->title);
        $attributes = [
            'subject' => $subject,
            'html' => [
                t('Error: %s', $subject),
                t('Error: %s', $throwable->getMessage()),
                t('File: %s', $throwable->getFile()),
                t('Line: %s', $throwable->getLine()),
            ],
        ];

        $user = User::find(config('config.admin.user_id'));
        $user->notify(new Generic($attributes));
    }
}
