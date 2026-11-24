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

use App\Presenters\TeamPresenter;
use App\Traits\ClearsResponseCache;
use App\Traits\Presentable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Team
 */
class Team extends BaseEloquentModel
{
    use ClearsResponseCache, HasFactory, Presentable;

    /**
     * {@inheritDoc}
     */
    protected $table = 'teams';

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'team_category_id',
        'first_name',
        'last_name',
        'title',
        'department',
        'institution',
    ];

    /**
     * @var string
     */
    protected $presenter = TeamPresenter::class;

    /**
     * TeamCategory relationship.
     *
     * @return BelongsTo
     */
    public function teamCategory()
    {
        return $this->belongsTo(TeamCategory::class);
    }

    /**
     * Define the full name attribute.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name.' '.$this->last_name,
        );
    }
}
