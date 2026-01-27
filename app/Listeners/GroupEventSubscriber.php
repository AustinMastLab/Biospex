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

namespace App\Listeners;

use App\Services\Group\GroupService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Class GroupEventSubscriber
 */
class GroupEventSubscriber
{
    /**
     * GroupSessionEventListener constructor.
     */
    public function __construct(protected GroupService $groupService) {}

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events)
    {
        $events->listen(
            Login::class,
            'App\Listeners\GroupEventSubscriber@onUserLogin'
        );

        $events->listen(
            Logout::class,
            'App\Listeners\GroupEventSubscriber@onUserLogout'
        );

        $events->listen(
            'group.saved',
            'App\Listeners\GroupEventSubscriber@setUserGroupSession'
        );

        $events->listen(
            'group.deleted',
            'App\Listeners\GroupEventSubscriber@setUserGroupSession'
        );

    }

    /**
     * Handle user login events.
     */
    public function onUserLogin($event)
    {
        \Log::info('User logged in: '.Auth::id());
        $this->setUserGroupSession();
    }

    /**
     * Handle user logout.
     */
    public function onUserLogout($event)
    {
        \Log::info('User logged out: '.Auth::id());
        Session::flush();
    }

    /**
     * Set the user groups inside a session variable.
     *
     * @param  null  $groupId
     */
    public function setUserGroupSession($groupId = null): void
    {
        $groupIds = $this->groupService->getUserGroupIds(Auth::id());

        $groups = $groupId === null ? $groupIds : $groupIds->diff([$groupId]);

        \Log::info('User group session updated for user: '.Auth::id());

        Session::put('groupIds', $groups->toArray());
    }
}
