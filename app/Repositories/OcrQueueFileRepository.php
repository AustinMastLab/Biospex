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

namespace App\Repositories;

use App\Models\OcrQueueFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Class OcrQueueFileRepository
 */
class OcrQueueFileRepository extends BaseRepository
{
    /**
     * OcrQueueFileRepository constructor.
     */
    public function __construct(OcrQueueFile $ocrQueueFile)
    {
        $this->model = $ocrQueueFile;
    }

    /**
     * Get OcrQueueFile query.
     */
    public function getOcrQueueFileQuery(int $queueId): LazyCollection
    {
        return $this->model->where('queue_id', $queueId)->cursor();
    }

    /**
     * Get OcrQueueFile empty.
     *
     * @return Builder[]|Collection
     */
    public function getUnprocessedOcrQueueFiles(int $queueId, int $take = 50): Collection|array
    {
        return $this->model->where('queue_id', $queueId)->where('processed', 0)->take($take)->get();
    }
}
