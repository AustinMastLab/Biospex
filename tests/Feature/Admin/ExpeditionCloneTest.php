<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
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

use App\Models\Expedition;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;

it('opens the clone form for a member of the expedition\'s group', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create(['user_id' => $user->id]);
    $user->assignGroup($group);
    $expedition = Expedition::factory()->for(Project::factory()->create(['group_id' => $group->id]))->create();

    $this->actingAs($user)
        ->get(route('admin.expeditions.clone', $expedition))
        ->assertOk()
        ->assertSee('Select subjects for the Expedition');
});

it('turns away a user outside the expedition\'s group', function () {
    $expedition = Expedition::factory()->create();

    $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
        ->get(route('admin.expeditions.clone', $expedition))
        ->assertRedirect(route('admin.projects.index'));
});
