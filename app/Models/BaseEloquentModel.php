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

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseEloquentModel
 *
 * Base Eloquent model class that provides common functionality and configuration
 * for all application models. This class handles database connection management,
 * environment-specific configurations, and serves as the foundation for all
 * other model classes in the application.
 *
 * Key Features:
 * - Automatic database connection switching for testing environments
 * - Standardized primary key configuration
 * - Common base functionality for all application models
 * - Environment-aware database connection management
 *
 * @mixin Eloquent
 */
class BaseEloquentModel extends Model
{
    /**
     * The database connection name for the model.
     * Defaults to MySQL but switches to SQLite during testing.
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;  // Enable for auto-increment IDs

    /**
     * The primary key for the model.
     * Standardized across all application models.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Create a new Eloquent model instance.
     *
     * This constructor automatically switches the database connection to SQLite
     * when running in the testing environment, ensuring consistent test execution
     * while maintaining MySQL for production and development environments.
     *
     * @param  array<string, mixed>  $attributes  Initial model attributes
     */
    public function __construct(array $attributes = [])
    {
        if (\App::environment('testing')) {
            $this->connection = 'sqlite';
        }

        parent::__construct($attributes);
    }

    /**
     * Save the model to the database.
     *
     * Fix for cases where auto-increment ID is not populated after save (e.g., Laravel 12 hydration edge cases).
     */
    public function save(array $options = []): bool
    {
        $result = parent::save($options);

        // If save succeeded, it's a fresh insert, auto-increment is enabled, but key is still null—fetch it manually
        if ($result && $this->wasRecentlyCreated && $this->getIncrementing() && is_null($this->getKey())) {
            $lastId = $this->getConnection()->getPdo()->lastInsertId();
            if ($lastId !== '0' && $lastId !== '') {  // Compare against string values only
                $this->setAttribute($this->getKeyName(), (int) $lastId);  // Cast to int when setting
                $this->syncOriginalAttribute($this->getKeyName());  // Sync to avoid "dirty" flags later
                $this->syncChanges();  // Optional: Refresh changed attributes
            }
        }

        return $result;
    }

    // ... existing code ...
}
