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

use App\Enums\OcrQueueStage;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class OcrQueue
 */
class OcrQueue extends BaseEloquentModel
{
    use HasFactory;

    /**
     * {@inheritDoc}
     */
    protected $table = 'ocr_queues';

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'project_id',
        'expedition_id',
        'queued',
        'stage',
        'files_ready',
        'total',
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
            'stage' => OcrQueueStage::class,
        ];
    }

    /**
     * Scope a query to only include queue.
     */
    #[Scope]
    protected function queued(Builder $query, int $queued): void
    {
        $query->where('queued', $queued);
    }

    /**
     * Scope a query to only include queue with no error.
     */
    #[Scope]
    protected function error(Builder $query, int $error): void
    {
        $query->where('error', $error);
    }

    /**
     * Scope a query to only include queue with ready_files.
     */
    #[Scope]
    protected function filesReady(Builder $query, int $ready): void
    {
        $query->where('files_ready', $ready);
    }

    /**
     * Project relation
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Expedition relation.
     */
    public function expedition()
    {
        return $this->belongsTo(Expedition::class);
    }

    /**
     * OcrQueueFiles relation.
     */
    public function files(): HasMany
    {
        return $this->hasMany(OcrQueueFile::class, 'queue_id', 'id');
    }
}
