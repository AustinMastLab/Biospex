<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
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

namespace App\Providers;

use App\Services\Helpers\CountService;
use App\Services\Helpers\DateService;
use App\Services\Helpers\TranscriptionMapService;
use App\Services\Transcriptions\PanoptesTranscriptionService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for registering helper services as singletons.
 *
 * This provider registers three helper services into the service container:
 * - counthelper: Provides counting functionality for transcriptions
 * - datehelper: Provides date manipulation and formatting utilities
 * - transcriptionmaphelper: Handles mapping and encoding of transcription fields
 */
class HelpersServiceProvider extends ServiceProvider
{
    /**
     * Register helper services as singletons in the service container.
     *
     * Binds the following services:
     * - 'counthelper': CountService instance with PanoptesTranscriptionService dependency
     * - 'datehelper': DateService instance for date operations
     * - 'transcriptionmaphelper': TranscriptionMapService with reserved encoded fields
     *   and mapped transcription field configurations from the zooniverse config
     */
    public function register(): void
    {
        $this->app->singleton('counthelper', function () {
            return new CountService(app(PanoptesTranscriptionService::class));
        });

        $this->app->singleton('datehelper', function () {
            return new DateService;
        });

        $this->app->singleton('transcriptionmaphelper', function () {
            return new TranscriptionMapService(
                $this->app['config']->get('zooniverse.reserved_encoded'),
                $this->app['config']->get('zooniverse.mapped_transcription_fields')
            );
        });
    }
}
