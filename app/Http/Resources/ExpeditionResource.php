<?php

/*
 * Copyright (C) 2015  Biospex
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
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Resources;

use App\Enums\ActorExpeditionState;
use App\Models\Expedition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public expedition as returned by the API.
 *
 * @mixin Expedition
 */
class ExpeditionResource extends JsonResource
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
            'description' => $this->description,
            'keywords' => $this->keywords,
            'completed' => (bool) $this->completed,
            'logo_url' => $this->present()->show_logo,
            'project' => [
                'uuid' => $this->project?->uuid,
                'title' => $this->project?->title,
            ],
            'stats' => [
                'transcriptions_completed' => (int) $this->stat?->local_transcriptions_completed,
                'transcriber_count' => (int) $this->stat?->transcriber_count,
                'percent_completed' => (float) $this->stat?->percent_completed,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.expeditions.show', $this->uuid),
                'project' => $this->project ? route('api.v1.projects.show', $this->project->uuid) : null,
                'participate' => $this->participateUrl(),
            ],
        ];
    }

    /**
     * Zooniverse classify url, only while the expedition is live there (as on the public card).
     */
    protected function participateUrl(): ?string
    {
        if ($this->panoptesProject === null || $this->zooActorExpedition?->state !== ActorExpeditionState::Processing) {
            return null;
        }

        return $this->panoptesProject->present()->participateUrl();
    }
}
