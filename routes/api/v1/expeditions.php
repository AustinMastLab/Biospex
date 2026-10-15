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

use App\Http\Controllers\Api\V1\ExpeditionController;

Route::middleware('ability:public:read')->group(function () {
    Route::get('/expeditions', [ExpeditionController::class, 'index'])->name('api.v1.expeditions.index');
    Route::get('/expeditions/{uuid}', [ExpeditionController::class, 'show'])->name('api.v1.expeditions.show');
});
