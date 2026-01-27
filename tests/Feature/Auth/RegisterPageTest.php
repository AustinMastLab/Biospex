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

use App\Models\Group;
use App\Models\GroupInvite;
use App\Models\User;

describe('Register Page Basic Tests', function () {
    it('displays the register page successfully', function () {
        $response = $this->get(route('app.get.register'));

        $response->assertStatus(200);
    });

    it('returns the correct view for registration', function () {
        $response = $this->get(route('app.get.register'));

        $response->assertViewIs('auth.register');
    });

    it('displays all required form fields', function () {
        $response = $this->get(route('app.get.register'))
            ->assertSee('Register Account')
            ->assertSee('Register')
            ->assertSee('Display Name')
            ->assertSee('Email')
            ->assertSee('Password')
            ->assertSee('Confirm Password');
    });
});

describe('User Registration Tests', function () {
    it('can register new user successfully', function () {
        $name = fake()->name;
        $email = fake()->email;
        $password = 'Password123!';

        $response = $this->post(route('app.post.register'), [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect(route('admin.projects.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        $this->assertDatabaseHas('profiles', [
            'first_name' => $name,
        ]);

        $this->assertAuthenticated();
    });
});

describe('Group Invite Registration Tests', function () {
    it('displays register page with group invite code in URL', function () {
        $group = Group::factory()->create();
        $invite = GroupInvite::create([
            'uuid' => fake()->uuid,
            'group_id' => $group->id,
            'email' => 'invited@example.com',
            'code' => 'TEST-INVITE-CODE',
        ]);

        $response = $this->get(route('app.get.register', ['invite' => $invite->uuid]));

        $response->assertStatus(200)
            ->assertViewIs('auth.register')
            ->assertViewHas('invite', $invite)
            ->assertSee('invited@example.com'); // Email should be pre-filled
    });

    it('can register new user with valid group invite', function () {
        $group = Group::factory()->create();
        $invite = GroupInvite::create([
            'uuid' => fake()->uuid,
            'group_id' => $group->id,
            'email' => 'invited@example.com',
            'code' => 'TEST-INVITE-CODE',
        ]);

        $name = fake()->name;
        $password = 'Password123!';

        $response = $this->post(route('app.post.register', ['invite' => $invite->uuid]), [
            'name' => $name,
            'email' => $invite->email, // Must match invite email
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect(route('admin.projects.index', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => $invite->email,
        ]);

        $user = User::where('email', $invite->email)->first();

        // User should be added to the group
        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        // Invite should be consumed
        $this->assertDatabaseMissing('group_invites', [
            'id' => $invite->id,
        ]);

        $this->assertAuthenticated();
    });

    it('cannot register with group invite when email does not match', function () {
        $group = Group::factory()->create();
        $invite = GroupInvite::create([
            'uuid' => fake()->uuid,
            'group_id' => $group->id,
            'email' => 'invited@example.com',
            'code' => 'TEST-INVITE-CODE',
        ]);

        $name = fake()->name;
        $differentEmail = 'different@example.com';
        $password = 'Password123!';

        $response = $this->post(route('app.post.register', ['invite' => $invite->uuid]), [
            'name' => $name,
            'email' => $differentEmail, // Different email from invite
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('users', [
            'email' => $differentEmail,
        ]);

        $this->assertGuest();
    });

    it('handles invalid group invite gracefully', function () {
        // Test with non-existent invite UUID - should return 404 as expected
        $fakeUuid = '12345678-1234-1234-1234-123456789012';

        $response = $this->get("/register/{$fakeUuid}");

        // Laravel returns 404 when model binding fails, which is the correct behavior
        $response->assertStatus(404);
    });
});
