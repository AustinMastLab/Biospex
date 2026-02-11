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
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

use App\Models\Profile;
use App\Models\User;

describe('User Account Access Control Tests', function () {
    it('redirects unauthenticated user to login', function () {
        $user = User::factory()->create();
        $response = $this->get(route('admin.users.edit', [$user]));

        $response->assertStatus(302);
    });

    it('redirects unauthorized user when trying to edit other user', function () {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $response = $this->actingAs($currentUser)->get(route('admin.users.edit', [$otherUser]));

        $response->assertStatus(302);
    });

    it('allows authorized user to access their own profile', function () {
        $user = User::factory()->verified()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->get(route('admin.users.edit', [$user]));

        $response->assertStatus(200);
    });
});

describe('User Profile Form Tests', function () {
    it('displays profile form fields for authorized user', function () {
        $user = User::factory()->verified()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->get(route('admin.users.edit', [$user]));

        $response->assertSee('first_name')
            ->assertSee('last_name')
            ->assertSee('email')
            ->assertSee('timezone');
    });

    it('displays user data in form fields', function () {
        $user = User::factory()->verified()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->get(route('admin.users.edit', [$user]));

        $response->assertSee($profile->first_name)
            ->assertSee($profile->last_name)
            ->assertSee($user->email);
    });
});

describe('User Profile Update Tests', function () {
    it('can update profile information successfully', function () {
        $user = User::factory()->verified()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Hit page first due to unit test not working well with Redirect::back().
        $this->actingAs($user)->get(route('admin.users.edit', [$user]));

        $firstName = fake()->firstName;
        $lastName = fake()->lastName;

        // Must satisfy max:32 validation rule in EditUserFormRequest.
        $email = fake()->unique()->userName.'@t.test';

        $timezone = fake()->timezone;

        $response = $this->actingAs($user)->put(route('admin.users.update', [$user]), [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'timezone' => $timezone,
        ]);

        $response->assertRedirect(route('admin.users.edit', [$user]));

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'timezone' => $timezone,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $email,
        ]);
    });

    it('can update profile with notification settings', function () {
        $user = User::factory()->verified()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Hit page first due to unit test not working well with Redirect::back().
        $this->actingAs($user)->get(route('admin.users.edit', [$user]));

        $firstName = fake()->firstName;
        $lastName = fake()->lastName;

        // Must satisfy max:32 validation rule in EditUserFormRequest.
        $email = fake()->unique()->userName.'@t.test';

        $timezone = fake()->timezone;

        $response = $this->actingAs($user)->put(route('admin.users.update', [$user]), [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'timezone' => $timezone,
            'notification' => 1,
        ]);

        $response->assertRedirect(route('admin.users.edit', [$user]))
            ->assertSessionHas('success', 'User profile updated.');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'timezone' => $timezone,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $email,
        ]);
    });

    it('validates required fields during update', function () {
        $user = User::factory()->verified()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('admin.users.update', [$user]), [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors(['first_name', 'last_name', 'email']);
    });

    it('validates email uniqueness during update', function () {
        $user1 = User::factory()->verified()->create();
        $user2 = User::factory()->verified()->create();
        Profile::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user1)->put(route('admin.users.update', [$user1]), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $user2->email, // Try to use another user's email
            'timezone' => 'UTC',
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors(['email']);
    });
});
