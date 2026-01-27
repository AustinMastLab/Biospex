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

use function Pest\Laravel\post;

test('registration triggers two verification emails', function () {
    // Clear log before starting
    file_put_contents(storage_path('logs/laravel.log'), '');
    $response = post(route('app.post.register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
    $response->assertRedirect(route('verification.notice'));
    $logContent = file_get_contents(storage_path('logs/laravel.log'));
    $count = substr_count($logContent, 'Verification email notification is being sent');
    expect($count)->toBe(2);
    // Output the log content for analysis
    echo $logContent;
});
