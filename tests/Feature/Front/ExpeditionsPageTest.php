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

use App\Models\Expedition;

describe('Expeditions Page Tests', function () {
    it('displays the expeditions page successfully', function () {
        $response = $this->get(route('front.expeditions.index'));

        $response->assertStatus(200);
    });

    it('returns the correct view for expeditions page', function () {
        $response = $this->get(route('front.expeditions.index'));

        $response->assertViewIs('front.expedition.index');
    });

    it('passes required data to the view', function () {
        $response = $this->get(route('front.expeditions.index'));

        $response->assertViewHas(['expeditions', 'expeditionsCompleted']);
    });

    it('displays expeditions page with data when expeditions exist', function () {
        // Create some test expeditions
        Expedition::factory()->count(3)->create([
            'completed' => false,
        ]);

        $response = $this->get(route('front.expeditions.index'));

        // Check that the page displays content related to expeditions
        $response->assertSee('Expeditions'); // Page title or heading
    });

    it('displays page correctly with completed expeditions', function () {
        // Create some completed expeditions
        Expedition::factory()->count(2)->create([
            'completed' => true,
        ]);

        $response = $this->get(route('front.expeditions.index'));

        // Check that the page loads successfully with completed expeditions
        $response->assertStatus(200)
            ->assertViewHas(['expeditions', 'expeditionsCompleted']);
    });
});
