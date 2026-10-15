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

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public project as returned by the API.
 *
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'organization' => $this->organization,
            'organization_website' => $this->organization_website,
            'description_short' => $this->description_short,
            'description_long' => $this->description_long,
            'geographic_scope' => $this->geographic_scope,
            'taxonomic_scope' => $this->taxonomic_scope,
            'temporal_scope' => $this->temporal_scope,
            'keywords' => $this->keywords,
            'logo_url' => $this->present()->show_logo,
            'expeditions_count' => (int) $this->expeditions_count,
            'transcriptions_completed' => (int) $this->expedition_stats_sum_transcriptions_completed,
            'created_at' => $this->created_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.projects.show', $this->uuid),
                'expeditions' => route('api.v1.expeditions.index', ['project_uuid' => $this->uuid]),
                'page' => route('front.projects.show', $this->slug),
            ],
        ];
    }
}
