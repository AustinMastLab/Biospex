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

use App\Enums\ActorExpeditionState;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ActorExpedition
 *
 * Represents the pivot model for the many-to-many relationship between
 * Actor and Expedition models. This model stores additional information
 * about the processing state, progress, and configuration for each
 * actor-expedition combination.
 *
 * Key Features:
 * - Supports caching for improved performance
 * - Tracks processing state and progress information
 * - Manages actor order and expert status for expeditions
 * - Stores error information and processing totals
 */
class ActorExpedition extends BaseEloquentModel
{
    /**
     * The name of the database table associated with the model.
     *
     * @var string
     */
    protected $table = 'actor_expedition';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'actor_id',      // Foreign key reference to the Actor model
        'expedition_id', // Foreign key reference to the Expedition model
        'state',         // Current processing state of the actor-expedition
        'total',         // Total number of items processed
        'error',         // Error message if processing failed
        'order',         // Processing order for this actor in the expedition
        'expert',        // Boolean flag indicating if this is an expert review
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => ActorExpeditionState::class,
        ];
    }

    /**
     * Define a many-to-one relationship with the Actor model.
     *
     * This pivot model belongs to a single Actor, representing the
     * actor that processes the expedition.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    /**
     * Define a many-to-one relationship with the Expedition model.
     *
     * This pivot model belongs to a single Expedition, representing the
     * expedition being processed by the actor.
     */
    public function expedition(): BelongsTo
    {
        return $this->belongsTo(Expedition::class);
    }
}
