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

use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AmChart
 *
 * Represents chart data storage for AmCharts visualization library.
 * This model stores chart configurations, series data, and visualization
 * data associated with projects, enabling dynamic chart generation and display.
 *
 * Key Features:
 * - Supports caching for improved performance
 * - Automatic JSON encoding/decoding for series and data attributes
 * - Maintains relationship with Project model
 * - Stores chart configuration and visualization data
 */
class AmChart extends BaseEloquentModel
{
    use ClearsResponseCache, HasFactory;

    /**
     * The name of the database table associated with the model.
     *
     * @var string
     */
    protected $table = 'amcharts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'project_id',   // Foreign key reference to the Project model
        'series',       // Chart series configuration data (JSON)
        'data',         // Chart data points and values (JSON)
    ];

    /**
     * Define a many-to-one relationship with the Project model.
     *
     * An AmChart belongs to a single Project, representing the chart
     * data and configuration associated with that project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Define the data attribute accessor and mutator.
     *
     * This attribute automatically handles JSON encoding/decoding for the
     * chart data field, allowing seamless storage and retrieval of array
     * data as JSON in the database.
     */
    protected function data(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value)
        );
    }

    /**
     * Define the series attribute accessor and mutator.
     *
     * This attribute automatically handles JSON encoding/decoding for the
     * chart series configuration field, allowing seamless storage and
     * retrieval of array data as JSON in the database.
     */
    protected function series(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value)
        );
    }
}
