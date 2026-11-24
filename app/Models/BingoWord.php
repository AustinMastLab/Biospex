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
 * Class BingoWord
 */
class BingoWord extends BaseEloquentModel
{
    use ClearsResponseCache, HasFactory;

    /**
     * {@inheritDoc}
     */
    protected $table = 'bingo_words';

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'bingo_id',
        'word',
        'definition',
    ];

    /**
     * Bingo relation.
     */
    public function bingo(): BelongsTo
    {
        return $this->belongsTo(Bingo::class);
    }
}
