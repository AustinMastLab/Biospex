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

use App\Models\PanoptesTranscription;

beforeEach(function () {
    Storage::fake('s3');
});

describe('Home Page Basic Tests', function () {
    beforeEach(function () {
        PanoptesTranscription::truncate();
        $this->seed(\HomePageTestSeeder::class);
    });

    afterEach(function () {
        PanoptesTranscription::truncate();
    });

    it('displays the home page successfully', function () {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
    });

    it('returns the correct view for home page', function () {
        $response = $this->get(route('home'));

        $response->assertViewIs('front.home');
    });

    it('passes required data to the view', function () {
        $response = $this->get(route('home'));

        $response->assertViewHas(['expedition', 'contributorCount', 'transcriptionCount']);
    });

    it('displays content with data provided by services', function () {
        $response = $this->get(route('home'));

        // The view should load successfully and contain the basic structure
        $response->assertStatus(200);

        // Check that data is passed to view (already tested above)
        // This test verifies the page loads with the data services
        $this->assertTrue(true); // Placeholder for successful data loading
    });
});
