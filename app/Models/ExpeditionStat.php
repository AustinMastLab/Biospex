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

namespace App\Models;

use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ExpeditionStat
 */
class ExpeditionStat extends BaseEloquentModel
{
    use ClearsResponseCache, HasFactory;

    /**
     * {@inheritDoc}
     */
    protected $table = 'expedition_stats';

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'expedition_id',
        'local_subject_count',
        'subject_count',
        'transcriptions_goal',
        'local_transcriptions_completed',
        'transcriptions_completed',
        'transcriber_count',
        'percent_completed',
    ];

    /**
     * Return Expedition relation.
     */
    public function expedition(): BelongsTo
    {
        return $this->belongsTo(Expedition::class);
    }
}
