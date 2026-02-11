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

use App\Http\Controllers\Admin\ExpeditionCloneController;
use App\Http\Controllers\Admin\ExpeditionController;
use App\Http\Controllers\Admin\ExpeditionExportController;
use App\Http\Controllers\Admin\ExpeditionToolController;

Route::resource('expeditions', ExpeditionController::class)->except(['create', 'store'])->names([
    'index' => 'admin.expeditions.index',
    'show' => 'admin.expeditions.show',
    'edit' => 'admin.expeditions.edit',
    'update' => 'admin.expeditions.update',
    'destroy' => 'admin.expeditions.delete',
]);

Route::get('expeditions/{project}/create', [ExpeditionController::class, 'create'])->name('admin.expeditions.create');
Route::post('expeditions/{project}/store', [ExpeditionController::class, 'store'])->name('admin.expeditions.store');

// # New
Route::get('expeditions/{expedition}/clone', ExpeditionCloneController::class)->name('admin.expeditions.clone');
Route::get('expeditions/{expedition}/tools', ExpeditionToolController::class)->name('admin.expeditions.tools');

Route::get('expeditions/{expedition}/export', ExpeditionExportController::class)->name('admin.expeditions.export');
