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
use App\Http\Requests\EventFormRequest;
use App\Models\Event;
use App\Services\Event\EventService;
use App\Services\Permission\CheckPermission;
use App\Services\Project\ProjectService;
use Redirect;
use Throwable;
use View;

/**
 * Class EventController
 */
class EventController extends Controller
{
    /**
     * EventController constructor.
     */
    public function __construct(
        protected EventService $eventService,
        protected ProjectService $projectService
    ) {}

    /**
     * Display events.
     */
    public function index(): mixed
    {
        try {
            return View::make('admin.event.index');
        } catch (Throwable $throwable) {

            return Redirect::route('admin.projects.index')
                ->with('error', t('An error occurred when retrieving Event records.'));
        }
    }

    /**
     * Create event.
     */
    public function create(): mixed
    {
        $projects = $this->projectService->getProjectEventSelect();

        return View::make('admin.event.create', compact('projects'));
    }

    /**
     * Store Event.
     */
    public function store(EventFormRequest $request): mixed
    {
        try {
            $event = $this->eventService->store($request->all());

            return Redirect::route('admin.events.show', [$event])->with('success', t('Record was created successfully.'));
        } catch (Throwable $throwable) {

            return Redirect::route('admin.events.index')->with('error', t('An error occurred when saving record.'));
        }
    }

    /**
     * Show event.
     */
    public function show(Event $event): mixed
    {
        if (! CheckPermission::handle('read', $event)) {
            return Redirect::route('admin.events.index');
        }

        $this->eventService->getAdminShow($event);

        return View::make('admin.event.show', compact('event'));
    }

    /**
     * Edit event.
     */
    public function edit(Event $event): mixed
    {
        if (! CheckPermission::handle('update', $event)) {
            return back();
        }

        $this->eventService->edit($event);
        $projects = $this->projectService->getProjectEventSelect();

        return View::make('admin.event.edit', compact('event', 'projects'));
    }

    /**
     * Update Event.
     */
    public function update(Event $event, EventFormRequest $request): mixed
    {
        if (! CheckPermission::handle('update', $event)) {
            return Redirect::route('admin.events.index');
        }

        $result = $this->eventService->update($request->all(), $event);

        if ($result) {
            return Redirect::route('admin.events.show', [$event])->with('success', t('Record was updated successfully.'));
        }

        return Redirect::route('admin.events.edit', [$event])->with('error', t('Error while updating record.'));
    }

    /**
     * Delete Event.
     */
    public function destroy(Event $event): mixed
    {
        if (! CheckPermission::handle('delete', $event)) {
            return Redirect::route('admin.events.index');
        }

        return $event->delete() ?
            Redirect::route('admin.events.index')
                ->with('success', t('Record has been scheduled for deletion and changes will take effect in a few minutes.')) :
            Redirect::route('admin.events.edit', [$event])
                ->with('error', t('An error occurred when deleting record.'));
    }
}
