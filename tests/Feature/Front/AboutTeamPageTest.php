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

use App\Models\Team;
use App\Models\TeamCategory;

describe('About Team Page Basic Tests', function () {
    it('displays the about team page successfully', function () {
        $response = $this->get(route('front.teams.index'));

        $response->assertStatus(200);
    });

    it('returns the correct view for about team page', function () {
        $response = $this->get(route('front.teams.index'));

        $response->assertViewIs('front.team.index');
    });
});

describe('Team Content Tests', function () {
    it('displays team categories and members when available', function () {
        TeamCategory::factory()->count(3)
            ->has(Team::factory()->count(2))
            ->create();

        $response = $this->get(route('front.teams.index'));

        $names = TeamCategory::all()->pluck('name')->toArray();

        foreach ($names as $name) {
            $response->assertSee($name);
        }

        $response->assertStatus(200);
    });

    it('displays team member names when available', function () {
        $category = TeamCategory::factory()
            ->has(Team::factory()->count(2), 'teams')
            ->create();

        $response = $this->get(route('front.teams.index'));

        foreach ($category->teams as $team) {
            $response->assertSee($team->first_name)
                ->assertSee($team->last_name);
        }
    });
});
