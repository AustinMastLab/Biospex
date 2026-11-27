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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expedition;
use View;

/**
 * This controller handles the rendering and processing of GeoLocate forms.
 */
class GeolocateFormController extends Controller
{
    /**
     * The GeoLocate Export Form (App\Livewire\Admin\GeoLocateExportForm), loaded into the global modal.
     */
    public function index(Expedition $expedition): \Illuminate\View\View
    {
        return View::make('admin.geolocate.partials.form-show', compact('expedition'));
    }
}
