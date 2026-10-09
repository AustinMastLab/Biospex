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
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('export:queue')->everyMinute()->withoutOverlapping();
Schedule::command('tesseract:ocr-process')->everyMinute()->withoutOverlapping();
Schedule::command('cache:prune-stale-tags')->hourly()->withoutOverlapping();
// Schedule::command('queue:prune-batches --hours=48 --unfinished=72')->daily();

// Clean bingo maps
Schedule::command('bingo:clean')->dailyAt('10:05');

// Run lambda-reconciliation check every morning at 6:00
Schedule::command('app:check-lambda-reconcile')->dailyAt('6:00');

if ($this->app->environment('production')) {
    // Trigger workflow manager to handle csv creation and updating expedition/project
    Schedule::command('workflow:manage')->daily();

    // Clean efs directories for files over 72 hours old.
    // Schedule::command('app:clean-efs-dirs')->daily();
}
