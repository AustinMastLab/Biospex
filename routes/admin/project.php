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

use App\Http\Controllers\Admin\ProjectCloneController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectStatsController;
use App\Http\Controllers\Admin\ProjectSubjectController;

Route::get('projects', [ProjectController::class, 'index'])->name('admin.projects.index');
Route::get('projects/create', [ProjectController::class, 'create'])->name('admin.projects.create');
Route::post('projects/store', [ProjectController::class, 'store'])->name('admin.projects.store');
Route::get('projects/{project}', [ProjectController::class, 'show'])->name('admin.projects.show');
Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('admin.projects.edit');
Route::put('projects/{project}', [ProjectController::class, 'update'])->name('admin.projects.update');
Route::delete('projects/{project}', [ProjectController::class, 'delete'])->name('admin.projects.destroy');

Route::get('projects/{project}/statistics', ProjectStatsController::class)->name('admin.project-stats.index');
Route::get('projects/{project}/clone', ProjectCloneController::class)->name('admin.projects.clone');
Route::get('projects/{project}/subjects', [ProjectSubjectController::class, 'index'])->name('admin.project-subjects.index');
Route::delete('projects/{project}/subject', [ProjectSubjectController::class, 'destroy'])->name('admin.project-subjects.destroy');
