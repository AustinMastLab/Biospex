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

use App\Presenters\SiteAssetPresenter;
use App\Traits\ClearsResponseCache;
use App\Traits\Presentable;
use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class SiteAsset
 */
class SiteAsset extends BaseEloquentModel
{
    use ClearsResponseCache, HasFactory, Presentable, UuidTrait;

    /**
     * {@inheritDoc}
     */
    protected $table = 'site_assets';

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'title',
        'description',
        'download_path',
        'order',
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

    protected string $presenter = SiteAssetPresenter::class;

    /**
     * Boot functions.
     */
    public static function boot(): void
    {
        parent::boot();

        static::bootUuidTrait();
    }

    /**
     * SiteAsset constructor.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
}
