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

use App\Models\User;
use App\Services\Asset\Thumbnail;

it('shows the image URL as escaped text that wraps inside the dialog', function () {
    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));

    $response = $this->get(route('admin.images.preview', [
        'url' => 'https://example.org/image.jpg?a=1&b=<script>alert(1)</script>',
        'url-view' => 'true',
    ]));

    $response->assertOk()
        ->assertSee('overflow-wrap: anywhere', false)
        ->assertSee('https://example.org/image.jpg?a=1&amp;b=&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>', false);
});

it('shows a thumbnail that scales to the dialog', function () {
    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
    $this->mock(Thumbnail::class)
        ->shouldReceive('getThumbnail')
        ->with('https://example.org/image.jpg')
        ->andReturn('jpeg-bytes');

    $response = $this->get(route('admin.images.preview', ['url' => 'https://example.org/image.jpg']));

    $response->assertOk()
        ->assertSee('<img class="img-fluid" src="data:image/jpeg;base64,'.base64_encode('jpeg-bytes').'"', false);
});
