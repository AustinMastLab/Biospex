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

namespace App\Services\Actor\GeoLocate;

use App\Enums\ActorExpeditionState;
use App\Models\Expedition;
use App\Models\GeoLocateCommunity;
use App\Models\GeoLocateDataSource;
use App\Services\Api\GeoLocateApi;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class GeoLocateStatService
 * Provides services for managing and updating geographic community and data source statistics.
 */
class GeoLocateStatService
{
    /**
     * GeoLocateStatService constructor.
     */
    public function __construct(
        protected GeoLocateCommunity $geoLocateCommunity,
        protected GeoLocateDataSource $geoLocateDataSource,
        protected GeoLocateApi $geoLocateApi
    ) {}

    /**
     * Saves the community data source by updating or creating the community and its corresponding data source.
     * Updates the expedition actor's pivot state to reflect the changes.
     *
     * @param  array  $data  An associative array containing the community and data source details.
     * @param  Expedition  $expedition  The expedition instance associated with the community and data source.
     *
     * @throws GuzzleException
     */
    public function saveCommunityDataSource(array $data, Expedition $expedition): void
    {
        if (! empty($data['community'])) {
            $this->getCommunityDataSource($data['community']);
            $community = $this->updateOrCreateCommunity($expedition->project_id, $data['community']);
        } else {
            $community = $this->geoLocateCommunity->find($data['community_id']);
        }

        if (! empty($data['data_source'])) {
            $this->getCommunityDataSource($community->name, $data['data_source']);
        }

        $this->updateOrCreateDataSource($expedition, $community->id, $data['data_source']);

        $expedition->actors()->updateExistingPivot(config('geolocate.actor_id'), [
            'state' => ActorExpeditionState::Processing->value,
        ]);
    }

    /**
     * Updates an existing community or creates a new one based on the given project ID and community name.
     *
     * @param  int  $projectId  The ID of the project to which the community belongs.
     * @param  string  $community  The name of the community to be updated or created.
     * @return GeoLocateCommunity The updated or newly created GeoLocateCommunity instance.
     */
    public function updateOrCreateCommunity(int $projectId, string $community): GeoLocateCommunity
    {
        $attributes = [
            'project_id' => $projectId,
            'name' => $community,
        ];
        $values = [
            'project_id' => $projectId,
            'name' => $community,
        ];

        return $this->geoLocateCommunity->updateOrCreate($attributes, $values);
    }

    /**
     * Updates an existing data source or creates a new one for the given expedition and community.
     *
     * @param  Expedition  $expedition  The expedition instance associated with the data source.
     * @param  int  $communityId  The ID of the community to which the data source belongs.
     * @param  string  $dataSource  The name or identifier of the data source being updated or created.
     * @return GeoLocateDataSource The created or updated data source instance.
     */
    public function updateOrCreateDataSource(Expedition $expedition, int $communityId, string $dataSource): GeoLocateDataSource
    {
        $attributes = [
            'project_id' => $expedition->project_id,
            'expedition_id' => $expedition->id,
        ];
        $values = [
            'project_id' => $expedition->project_id,
            'expedition_id' => $expedition->id,
            'geo_locate_community_id' => $communityId,
            'data_source' => $dataSource,
        ];

        return $this->geoLocateDataSource->updateOrCreate($attributes, $values);
    }

    /**
     * Updates or creates a GeoLocate community statistic using the provided ID and data.
     *
     * @param  int  $id  The unique identifier of the GeoLocate community.
     * @param  array  $data  An associative array containing the data to be updated or created for the community.
     */
    public function updateGeoLocateCommunityStat(int $id, array $data): void
    {

        $this->geoLocateCommunity->updateOrCreate(['id' => $id], ['data' => $data]);
    }

    /**
     * Updates the GeoLocate data source record with the provided data.
     *
     * @param  int  $id  The unique identifier of the GeoLocate data source to be updated.
     * @param  array  $data  An associative array containing the new data to update the data source.
     *
     * @throws \Exception
     */
    public function updateGeoLocateDataSourceStat(int $id, array $data): void
    {
        $this->geoLocateDataSource->update(['id' => $id], ['data' => $data]);
    }

    /**
     * Retrieves community data and optional data source information from the GeoLocate API.
     *
     * @param  string  $cname  The name of the community to retrieve data for.
     * @param  string|null  $dname  The optional name of the data source to retrieve specific details.
     * @return array An associative array containing the retrieved community and data source data.
     *
     * @throws \Exception|GuzzleException If an error is returned in the API response.
     */
    public function getCommunityDataSource(string $cname, ?string $dname = null): array
    {
        $uri = $this->geoLocateApi->buildStatsUri($cname, $dname);
        $this->geoLocateApi->setHttpProvider();

        $response = json_decode($this->geoLocateApi->getStats($uri), true);

        if (isset($response['error'])) {
            throw new \Exception($response['error']);
        }

        return $response;
    }
}
