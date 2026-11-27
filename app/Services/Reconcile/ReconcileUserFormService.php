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

namespace App\Services\Reconcile;

use App\Models\Download;
use App\Models\Expedition;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Factory;

class ReconcileUserFormService
{
    /**
     * ReconcileUserFormService constructor.
     */
    public function __construct(
        protected Factory $storage,
        protected Download $download,
        protected Carbon $carbon
    ) {}

    /**
     * Store the user-reviewed reconciled CSV (the file at $path) as the expedition's Reconciled With User Opinion file.
     */
    public function reconciledWithUserFile(Expedition $expedition, string $path): bool
    {
        if ($this->storage->disk('s3')->exists(config('zooniverse.directory.reconciled-with-user').'/'.$expedition->id.'.csv')) {
            $this->storage->disk('s3')->delete(config('zooniverse.directory.reconciled-with-user').'/'.$expedition->id.'.csv');
        }

        if ($this->storage->disk('s3')->put(config('zooniverse.directory.reconciled-with-user').'/'.$expedition->id.'.csv', file_get_contents($path))) {
            $this->updateOrCreateReviewDownload($expedition->id, 'reconciled-with-user');

            return true;
        }

        return false;
    }

    /**
     * Update or create review download.
     */
    public function updateOrCreateReviewDownload(string $expeditionId, string $type): void
    {
        $values = [
            'expedition_id' => $expeditionId,
            'actor_id' => config('zooniverse.actor_id'),
            'file' => $expeditionId.'.csv',
            'type' => $type,
            'updated_at' => $this->carbon->now()->format('Y-m-d H:i:s'),
        ];
        $attributes = [
            'expedition_id' => $expeditionId,
            'actor_id' => config('zooniverse.actor_id'),
            'file' => $expeditionId.'.csv',
            'type' => $type,
        ];

        $this->download->updateOrCreate($attributes, $values);
    }
}
