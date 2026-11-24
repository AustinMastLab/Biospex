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

use App\Presenters\ProjectAssetPresenter;
use App\Traits\ClearsResponseCache;
use App\Traits\Presentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ProjectAsset
 */
class ProjectAsset extends BaseEloquentModel
{
    use ClearsResponseCache, HasFactory, Presentable;

    /**
     * {@inheritDoc}
     */
    protected $table = 'project_assets';

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'project_id',
        'type',
        'name',
        'description',
        'download_path',
    ];

    /**
     * @var string
     */
    protected $presenter = ProjectAssetPresenter::class;

    /**
     * Project constructor.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Project relationship.
     *
     * @return BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
