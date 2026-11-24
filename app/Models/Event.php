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

use App\Presenters\EventPresenter;
use App\Traits\ClearsResponseCache;
use App\Traits\Presentable;
use App\Traits\UuidTrait;
use DateTimeZone;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Event
 */
class Event extends BaseEloquentModel
{
    use ClearsResponseCache, HasFactory, Presentable, UuidTrait;

    /**
     * {@inheritDoc}
     */
    protected $table = 'events';

    /**
     * The attributes that should be cast.
     *
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'owner_id' => 'integer',
            'title' => 'string',
            'description' => 'string',
            'hashtag' => 'string',
            'contact' => 'string',
            'contact_email' => 'string',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'timezone' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'project_id',
        'owner_id',
        'title',
        'description',
        'hashtag',
        'contact',
        'contact_email',
        'start_date',
        'end_date',
        'timezone',
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
     * The presenter class to use for this model.
     */
    protected string $presenter = EventPresenter::class;

    /**
     * Model Boot
     */
    public static function boot(): void
    {
        parent::boot();
        static::bootUuidTrait();

    }

    /**
     * Project relationship.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Owner relationship.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * EventTeam relationship.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(EventTeam::class);
    }

    /**
     * Event transcription relationship.
     */
    public function transcriptions(): HasMany
    {
        return $this->hasMany(EventTranscription::class);
    }

    /**
     * Define the start date attribute.
     */
    protected function startDate(): Attribute
    {
        return Attribute::make(set: fn ($value) => $value->setTimezone(new DateTimeZone('UTC')));
    }

    /**
     * Define the end date attribute.
     */
    protected function endDate(): Attribute
    {
        return Attribute::make(set: fn ($value) => $value->setTimezone(new DateTimeZone('UTC')));
    }
}
