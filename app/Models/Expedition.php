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

use App\Presenters\ExpeditionPresenter;
use App\Traits\Presentable;
use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Expedition Model
 *
 * Represents an expedition within a project that manages scientific data collection
 * and transcription workflows. Expeditions contain subjects (specimens/images) that
 * are processed through various actors (Zooniverse, GeoLocate, etc.) for data extraction
 * and validation.
 */
class Expedition extends BaseEloquentModel
{
    use HasFactory, Presentable, UuidTrait;

    protected $table = 'expeditions';

    protected $fillable = ['uuid', 'project_id', 'title', 'description', 'keywords', 'logo_path', 'workflow_id', 'completed', 'locked',
    ];

    protected string $presenter = ExpeditionPresenter::class;

    /**
     * Get the route key for the model.
     *
     * @return string The route key name (uuid)
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Boot the model and initialize traits.
     */
    public static function boot(): void
    {
        parent::boot();
        static::bootUuidTrait();
    }

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes  Initial model attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Get the dashboard transcriptions for this expedition.
     */
    public function dashboard(): Expedition|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PusherTranscription::class, 'expedition_id');
    }

    /**
     * Get all downloads associated with this expedition.
     */
    public function downloads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Download::class);
    }

    /**
     * Get the GeoLocate CSV download for this expedition.
     */
    public function geoLocateCsvDownload(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Download::class)->where('actor_id', config('geolocate.actor_id'))->where('type', 'csv');
    }

    /**
     * Get the export queue entry for this expedition.
     */
    public function exportQueue(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ExportQueue::class);
    }

    /**
     * Get the GeoLocate export download for this expedition.
     */
    public function geoLocateExport(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Download::class)->where('actor_id', config('geolocate.actor_id'))->where('type', 'export');
    }

    /**
     * Get all OCR queue entries for this expedition.
     */
    public function ocrQueue(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OcrQueue::class);
    }

    /**
     * Get the project that owns this expedition.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the statistics for this expedition.
     */
    public function stat(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ExpeditionStat::class);
    }

    /**
     * Get all subjects associated with this expedition.
     */
    public function subjects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subject::class, 'expedition_ids', 'id')->where('expedition_ids', $this->id);
    }

    /**
     * Get the workflow associated with this expedition.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the workflow manager for this expedition.
     */
    public function workflowManager(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WorkflowManager::class);
    }

    /**
     * Get the Zooniverse export download for this expedition.
     */
    public function zooniverseExport(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Download::class)->where('actor_id', config('zooniverse.actor_id'))
            ->where('type', 'export');
    }

    /**
     * Get all actors associated with this expedition through the pivot table.
     */
    public function actors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'actor_expedition')
            ->withPivot('id', 'expedition_id', 'actor_id', 'state', 'total', 'error', 'order', 'expert')
            ->orderBy('order')
            ->withTimestamps();
    }

    /**
     * Get all actor expedition records for this expedition.
     */
    public function actorExpeditions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActorExpedition::class);
    }

    /**
     * Get the GeoLocate actor expedition record for this expedition.
     */
    public function geoActorExpedition(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ActorExpedition::class)->where('actor_id', config('geolocate.actor_id'));
    }

    /**
     * Get the Zooniverse actor expedition record for this expedition.
     */
    public function zooActorExpedition(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ActorExpedition::class)->where('actor_id', config('zooniverse.actor_id'));
    }

    /**
     * Get the Panoptes project associated with this expedition.
     */
    public function panoptesProject(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PanoptesProject::class);
    }

    /**
     * Get the GeoLocate form through the data source.
     */
    public function geoLocateForm(): HasOneThrough
    {
        return $this->hasOneThrough(GeoLocateForm::class, GeoLocateDataSource::class,
            'expedition_id', // Foreign key on GeoLocateDataSource table
            'id', // Foreign key on GeoLocateForm table
            'id', // Local key on the Expedition model
            'geo_locate_form_id' // Local key on the GeoLocateDataSource model
        );
    }

    /**
     * Get the GeoLocate community through the data source.
     */
    public function geoLocateCommunity(): HasOneThrough
    {
        return $this->hasOneThrough(GeoLocateCommunity::class, GeoLocateDataSource::class, 'expedition_id',
            // Foreign key on GeoLocateDataSource table
            'id', // Foreign key on GeoLocateForm table
            'id', // Local key on the Expedition model
            'geo_locate_community_id' // Local key on the GeoLocateDataSource model
        );
    }

    /**
     * Get the GeoLocate data source for this expedition.
     */
    public function geoLocateDataSource(): HasOne
    {
        return $this->hasOne(GeoLocateDataSource::class);
    }
}
