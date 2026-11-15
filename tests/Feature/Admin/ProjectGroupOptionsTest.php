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

use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
    $this->otherGroup = Group::factory()->create(['title' => 'Someone Elses Herbarium']);
    $this->project = Project::factory()->create(['group_id' => $this->otherGroup->id]);
});

/**
 * A verified user who belongs to a group of their own.
 */
function userWithOwnGroup(bool $admin = false): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignGroup(Group::factory()->create(['user_id' => $user->id, 'title' => 'My Own Herbarium']));

    if ($admin) {
        $user->assignGroup(Group::factory()->create(['title' => config('config.admin.group')]));
    }

    return $user;
}

it('selects the project group on the edit form for an admin outside that group', function () {
    $this->actingAs(userWithOwnGroup(admin: true));

    $response = $this->get(route('admin.projects.edit', $this->project));

    $response->assertOk()
        ->assertSee('selected=selected value="'.$this->otherGroup->id.'"', false)
        ->assertSee('My Own Herbarium');
});

it('lists every group on the clone form for an admin', function () {
    $this->actingAs(userWithOwnGroup(admin: true));

    $response = $this->get(route('admin.projects.clone', $this->project));

    $response->assertOk()
        ->assertSee('Someone Elses Herbarium')
        ->assertSee('My Own Herbarium');
});

it('lists only their own groups on the create form for a user who is not an admin', function () {
    $this->actingAs(userWithOwnGroup());

    $response = $this->get(route('admin.projects.create'));

    $response->assertOk()
        ->assertSee('My Own Herbarium')
        ->assertDontSee('Someone Elses Herbarium');
});
