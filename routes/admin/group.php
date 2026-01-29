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

use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\GroupGeoLocateFormController;
use App\Http\Controllers\Admin\GroupInviteController;
use App\Http\Controllers\Admin\GroupUserController;

Route::resource('groups', GroupController::class)->names([
    'index' => 'admin.groups.index',
    'create' => 'admin.groups.create',
    'store' => 'admin.groups.store',
    'show' => 'admin.groups.show',
    'edit' => 'admin.groups.edit',
    'update' => 'admin.groups.update',
    'destroy' => 'admin.groups.destroy',
]);

// Handle users in groups.
Route::delete('groups/{group}/delete-user/{user}', GroupUserController::class)->name('admin.groups-user.destroy');

Route::get('groups/{group}/invites', [GroupInviteController::class, 'create'])->name('admin.invites.create');
Route::post('groups/{group}/invites', [GroupInviteController::class, 'store'])->name('admin.invites.store');

// Handle geolocate forms in groups.
Route::delete('groups/{group}/delete-form/{form}', GroupGeoLocateFormController::class)->name('admin.groups-geolocate-form.destroy');
