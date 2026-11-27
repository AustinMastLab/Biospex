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

use App\Http\Controllers\Front\BingoController;
use App\Http\Controllers\Front\BingoJoinController;

Route::get('bingos', [BingoController::class, 'index'])->name('front.bingos.index');
Route::get('bingos/{bingo}', [BingoController::class, 'show'])->name('front.bingos.show');

// Each visitor's card is their own player, so the card page is never served from the response cache.
Route::get('bingos/{bingo}/join', [BingoJoinController::class, 'index'])
    ->middleware('doNotCacheResponse')
    ->name('front.bingos.join');
