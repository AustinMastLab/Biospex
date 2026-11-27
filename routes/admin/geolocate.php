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

use App\Http\Controllers\Admin\GeolocateCommunityController;
use App\Http\Controllers\Admin\GeoLocateController;
use App\Http\Controllers\Admin\GeolocateFormController;
use App\Http\Controllers\Admin\GeoLocateStatController;

Route::delete('geolocates/{expedition}/destroy', [GeoLocateController::class, 'destroy'])->name('admin.geolocates.destroy');

Route::get('geolocates/stat/{expedition}', [GeoLocateStatController::class, 'index'])->name('admin.geolocate-stat.index');
Route::post('geolocates/stat/{expedition}/update', [GeoLocateStatController::class, 'update'])->name('admin.geolocate-stat.update');

Route::get('geolocates/form/{expedition}', [GeolocateFormController::class, 'index'])->name('admin.geolocate-form.index');

Route::get('geolocates/community/{expedition}/edit', [GeolocateCommunityController::class, 'edit'])->name('admin.geolocate-community.edit');
Route::delete('geolocates/community/{community}/destroy', [GeolocateCommunityController::class, 'destroy'])->name('admin.geolocate-community.destroy');
