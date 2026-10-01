<?php

namespace App\Models;

use Database\Factories\ExpeditionSaveRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpeditionSaveRequest extends BaseEloquentModel
{
    /** @use HasFactory<ExpeditionSaveRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'expedition_id',
        'user_id',
        'operation',
        'subject_ids',
        'revision',
        'status',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
            'revision' => 'integer',
            'subject_ids' => 'array',
        ];
    }

    public function expedition(): BelongsTo
    {
        return $this->belongsTo(Expedition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
