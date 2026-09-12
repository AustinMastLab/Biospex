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

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Event;
use View;

/**
 * Class EventController
 */
class EventController extends Controller
{
    /**
     * Displays Events on public page.
     */
    public function index(): \Illuminate\Contracts\View\View
    {
        return View::make('front.event.index');
    }

    /**
     * Display the show page for an event.
     */
    public function show(Event $event): \Illuminate\Contracts\View\View
    {
        $event->load(['project.lastPanoptesProject', 'teams:id,title,event_id']);

        return View::make('front.event.show', compact('event'));
    }
}
