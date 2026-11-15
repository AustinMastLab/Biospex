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

namespace App\Services\Group;

use App\Models\Group;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Class GroupService
 *
 * This service handles operations related to groups and their associated data,
 * such as retrieving, creating, updating, and deleting group resources.
 */
class GroupService
{
    /**
     * Constructor method for initializing the class with dependencies.
     *
     * @param  Group  $group  An instance of the Group class.
     * @param  User  $user  An instance of the User class.
     * @return void
     */
    public function __construct(
        public Group $group,
        public User $user
    ) {}

    /**
     * Retrieve a collection of groups with associated counts for projects, expeditions, and users,
     * filtered by the authenticated user's association with the groups.
     */
    public function getAdminIndex(): Collection
    {
        return $this->group->withCount(['projects', 'expeditions', 'users'])
            ->whereHas('users', function ($q) {
                $q->where('user_id', Auth::id());
            })->get();
    }

    /**
     * Stores a new group associated with the given user and request data.
     *
     * @param  User  $user  The user creating the group.
     * @param  array  $request  An array containing the group's creation details, including the title.
     * @return Group The newly created group instance.
     */
    public function storeGroup(User $user, array $request): Group
    {
        $group = $this->group->create(['user_id' => $user->id, 'title' => $request['title']]);

        $user->assignGroup($group);
        $admin = $this->user::find(1);
        $admin->assignGroup($group);

        event('group.saved');

        return $group;
    }

    /**
     * Display the specified group with its related data.
     *
     * @param  Group  &$group  The group instance to be displayed, with related data loaded.
     */
    public function showGroup(Group &$group): void
    {
        $group->load([
            'projects',
            'expeditions',
            'geoLocateForms.expeditions',
            'owner',
            'users',
        ])->loadCount('expeditions');
    }

    /**
     * Edits the given group by loading related owner and user profile data, and maps user IDs to their full names.
     *
     * @param  Group  $group  The group to be edited, passed by reference to modify its data.
     * @return Collection A collection mapping user IDs to their corresponding full names.
     */
    public function editGroup(Group &$group): Collection
    {
        $group->load(['owner', 'users']);

        return $group->users->mapWithKeys(function ($user) {
            return [$user->id => $user->profile->full_name];
        });
    }

    /**
     * Deletes a specified group after performing necessary checks to ensure
     * that no associated workflows or processes exist.
     *
     * @param  Group  $group  The group instance to be deleted, passed by reference.
     *
     * @throws Exception If the group contains associated workflows, processes, or dependencies.
     */
    public function deleteGroup(Group &$group): void
    {
        $group->loadCount(['panoptesProjects'])->load([
            'projects' => function ($q) {
                $q->withCount('workflowManagers');
            },
        ]);

        if ($group->panoptes_projects_count > 0 || $group->projects->sum('id') > 0) {
            throw new Exception(t('An Expedition workflow or process exists and cannot be deleted. Even if the process has been stopped locally, other services may need to refer to the existing Expedition.'));
        }
    }

    /**
     * Retrieves the total count of groups that the specified user is associated with.
     *
     * @param  int  $userId  The ID of the user whose group count is being retrieved.
     * @return int The total number of groups the user is a member of.
     */
    public function getUserGroupCount($userId): int
    {
        return $this->group->withCount([
            'users' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            },
        ])->whereHas('users', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->pluck('users_count')->sum();
    }

    /**
     * Groups the user can choose for a project, formatted for selection input: every group for an admin,
     * who can edit any project, otherwise the groups the user belongs to.
     *
     * @param  User  $user  The user whose groups are to be retrieved.
     * @return array<int, string> Group titles keyed by group ID.
     */
    public function getUsersGroupsSelect(User $user): array
    {
        if ($user->isAdmin()) {
            return $this->group->orderBy('title')->pluck('title', 'id')->toArray();
        }

        return $this->group->whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->pluck('title', 'id')->toArray();
    }

    /**
     * Retrieves a collection of group IDs associated with the specified user.
     * Used for user sessions.
     *
     * @param  int  $userId  The ID of the user for which to fetch associated group IDs.
     * @return Collection A collection of group IDs linked to the user.
     */
    public function getUserGroupIds($userId): Collection
    {
        return $this->group->whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get()->map(function ($item) {
            return $item['id'];
        });
    }
}
