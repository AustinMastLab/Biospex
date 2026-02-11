<?php

/*
 * Copyright (C) 2015  Biospex
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

use App\Http\Controllers\Front\EventController;
use App\Http\Controllers\Front\EventRateChartController;
use App\Http\Controllers\Front\EventScoreboardController;

// Event routes: events.index, events.show
Route::resource('events', EventController::class)->only(['index', 'show'])->names([
    'index' => 'front.events.index',
    'show' => 'front.events.show',
]);

// Event scoreboard and rate chart used for both front and admin
Route::get('event/{event}/scoreboard', EventScoreboardController::class)->name('event.get.scoreboard');
Route::get('event/{event}/rate/{timestamp?}', EventRateChartController::class)->name('event.get.rate');
