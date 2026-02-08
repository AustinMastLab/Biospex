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
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventSortController;
use App\Http\Controllers\Admin\EventTranscriptionExportController;
use App\Http\Controllers\Admin\EventUserExportController;

Route::post('events/sort', [EventSortController::class, 'index'])->name('admin.events_sort.index');

Route::resource('events', EventController::class)->names([
    'index' => 'admin.events.index',
    'create' => 'admin.events.create',
    'store' => 'admin.events.store',
    'show' => 'admin.events.show',
    'edit' => 'admin.events.edit',
    'update' => 'admin.events.update',
    'destroy' => 'admin.events.destroy',
]);

Route::get('events/{event}/transcriptions', [EventTranscriptionExportController::class, 'index'])
    ->name('admin.events_transcriptions.index');
Route::get('events/{event}/users', [EventUserExportController::class, 'index'])
    ->name('admin.events_users.index');
