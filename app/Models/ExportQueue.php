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

use App\Enums\ExportQueueStage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ExportQueue
 */
class ExportQueue extends BaseEloquentModel
{
    use HasFactory;

    /**
     * @ineritDoc
     */
    protected $table = 'export_queues';

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'expedition_id',
        'actor_id',
        'stage',
        'queued',
        'total',
        'files_ready',
        'error',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => ExportQueueStage::class,
        ];
    }

    /**
     * Get Expedition relation.
     */
    public function expedition(): BelongsTo
    {
        return $this->belongsTo(Expedition::class);
    }

    /**
     * Get Actor relation.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    /**
     * Get ExportQueueFile relation.
     */
    public function files(): HasMany
    {
        return $this->hasMany(ExportQueueFile::class, 'queue_id', 'id');
    }

    /**
     * Define the missing attribute.
     */
    protected function missing(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => empty($value) ? [] : unserialize($value),
            set: fn ($value) => serialize($value)
        );
    }
}
