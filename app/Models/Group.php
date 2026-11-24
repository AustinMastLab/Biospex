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

use App\Presenters\GroupPresenter;
use App\Traits\ClearsResponseCache;
use App\Traits\Presentable;
use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Represents a group entity in the system.
 *
 * Defines various relationships and traits associated with the group. This model is responsible
 * for interacting with the database table defined by the `$table` property and uses
 * UUID-based identification.
 */
class Group extends BaseEloquentModel
{
    use ClearsResponseCache, HasFactory, Presentable, UuidTrait;

    /**
     * The name of the table associated with the model.
     */
    protected $table = 'groups';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'title',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = ['id'];

    /**
     * The presenter class associated with the group.
     */
    protected string $presenter = GroupPresenter::class;

    /**
     * Get the route key name for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Boots the model and applies additional initialization logic.
     */
    public static function boot(): void
    {
        parent::boot();

        static::bootUuidTrait();
    }

    /**
     * Owner relationship.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Users relationship.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('group_id');
    }

    /**
     * Projects relationship.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->orderBy('title');
    }

    /**
     * PanoptesProjects relationship.
     */
    public function panoptesProjects(): HasManyThrough
    {
        return $this->hasManyThrough(PanoptesProject::class, Project::class);
    }

    /**
     * Expeditions relationship
     */
    public function expeditions(): HasManyThrough
    {
        return $this->hasManyThrough(Expedition::class, Project::class);
    }

    /**
     * Invites relationship.
     */
    public function invites(): HasMany
    {
        return $this->hasMany(GroupInvite::class);
    }

    /**
     * GeoLocateForm relation.
     */
    public function geoLocateForms(): HasMany
    {
        return $this->hasMany(GeoLocateForm::class);
    }
}
