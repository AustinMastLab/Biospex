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

use App\Http\Controllers\Admin\ExpertReconcileController;
use App\Http\Controllers\Admin\ExpertReconcileFormController;
use App\Http\Controllers\Admin\ExpertReconcilePublishController;

Route::get('reconciles/{expedition}', [ExpertReconcileController::class, 'index'])->name('admin.reconciles.index');
Route::get('reconciles/{expedition}/create', [ExpertReconcileController::class, 'create'])->name('admin.reconciles.create');
Route::put('reconciles/{expedition}', [ExpertReconcileController::class, 'update'])->name('admin.reconciles.update');

Route::post('reconciles/{expedition}/publish', ExpertReconcilePublishController::class)->name('admin.reconciles.publish');

Route::get('reconciles/{expedition}/edit', [ExpertReconcileFormController::class, 'edit'])->name('admin.reconcile-user-form.edit');
