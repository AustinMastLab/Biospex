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

namespace App\Services\Workflow;

use App\Models\Expedition;
use App\Models\Workflow;

class WorkflowService
{
    public function __construct(public Workflow $workflow) {}

    /**
     * Workflow options for a new expedition (create and clone): every enabled workflow, so a new expedition
     * can start on GeoLocate when that workflow is enabled in the admin.
     *
     * @return array<int|string, string>
     */
    public function getWorkflowSelect(): array
    {
        return ['' => '--Select--'] + $this->workflow->where('enabled', '=', 1)
            ->orderBy('id', 'asc')
            ->pluck('title', 'id')
            ->toArray();
    }

    /**
     * Workflow options for editing an expedition: its current workflow, plus the GeoLocate workflow when it
     * can change to it (see ExpeditionService::canChangeToGeoLocate()).
     *
     * @return array<int, string>
     */
    public function getEditWorkflowSelect(Expedition $expedition, bool $canChangeToGeoLocate): array
    {
        $ids = array_filter([$expedition->workflow_id, $canChangeToGeoLocate ? (int) config('geolocate.workflow_id') : null]);

        return $this->workflow->whereKey($ids)
            ->orderBy('id', 'asc')
            ->pluck('title', 'id')
            ->toArray();
    }
}
