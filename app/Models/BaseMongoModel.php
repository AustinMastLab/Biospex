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

use MongoDB\Laravel\Eloquent\Model;

/**
 * Class BaseMongoModel
 *
 * Base MongoDB model class that provides common functionality and configuration
 * for all MongoDB-based models in the application. This class extends the
 * Laravel MongoDB package's base model and provides enhanced attribute casting
 * and standardized configuration for MongoDB collections.
 *
 * Key Features:
 * - MongoDB connection management
 * - Enhanced attribute casting functionality
 * - Non-incrementing primary keys (MongoDB ObjectId)
 * - Mass assignment protection disabled by default
 * - Standardized MongoDB model configuration
 *
 * @mixin \Eloquent
 */
class BaseMongoModel extends Model
{
    /**
     * The database connection name for the model.
     * Uses MongoDB connection for all MongoDB-based models.
     *
     * @var string
     */
    protected $connection = 'mongodb';

    /**
     * The attributes that are not mass assignable.
     * Empty array allows mass assignment for all attributes.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * Indicates if the model should use auto-incrementing primary keys.
     * MongoDB uses ObjectId which is not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Create a new MongoDB Eloquent model instance.
     *
     * This constructor initializes the MongoDB model with the provided
     * attributes and sets up the necessary MongoDB-specific configurations.
     *
     * @param  array<string, mixed>  $attributes  Initial model attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Set a given attribute on the model with enhanced casting support.
     *
     * This method extends the base setAttribute functionality to ensure
     * that attribute casting is properly applied before setting values,
     * providing better data type consistency for MongoDB documents.
     *
     * @param  string  $key  The attribute name
     * @param  mixed  $value  The attribute value
     * @return Model|mixed|void
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasCast($key)) {
            $value = $this->castAttribute($key, $value);
        }

        return parent::setAttribute($key, $value);
    }
}
