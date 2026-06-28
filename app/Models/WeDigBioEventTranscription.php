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

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WeDigBioEventTranscription extends BaseEloquentModel
{
    protected $table = 'wedigbio_event_transcriptions';

    protected $fillable = [
        'classification_id',
        'project_id',
        'event_id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'classification_id' => 'int',
            'project_id' => 'int',
            'event_id' => 'int',
        ];
    }

    /**
     * Transcription relationship.
     */
    public function transcription(): HasOne
    {
        return $this->hasOne(PanoptesTranscription::class, '_id', 'classification_id');
    }

    /**
     * Project relationship.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Date relationship.
     */
    public function date(): BelongsTo
    {
        return $this->belongsTo(WeDigBioEvent::class, 'event_id', 'id');
    }

    /**
     * DateId scope.
     */
    public function scopeDateId($query, $arg): mixed
    {
        return $query->where('event_id', $arg);
    }
}
