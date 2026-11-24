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
use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class BingoUser
 */
class BingoUser extends BaseEloquentModel
{
    use ClearsResponseCache, HasFactory, UuidTrait;

    /**
     * {@inheritDoc}
     */
    protected $table = 'bingo_users';

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'bingo_id',
        'uuid',
        'ip',
        'latitude',
        'longitude',
        'city',
        'winner',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Model Boot
     */
    public static function boot(): void
    {
        parent::boot();
        static::bootUuidTrait();

    }

    /**
     * Bingo relation.
     */
    public function bingo(): BelongsTo
    {
        return $this->belongsTo(Bingo::class);
    }
}
