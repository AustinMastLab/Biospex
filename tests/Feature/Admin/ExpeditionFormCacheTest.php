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
use App\Models\ExpeditionStat;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * The subject selection token in an expedition form page.
 */
function selectionToken(string $html): ?string
{
    return preg_match('/name="subject-selection" value="([^"]+)"/', $html, $match) === 1 ? $match[1] : null;
}

it('starts a new subject selection on every visit to an expedition form, never a cached one', function (string $route) {
    config(['responsecache.enabled' => true]);
    Storage::fake('s3');
    $user = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create(['user_id' => $user->id]);
    $user->assignGroup($group);
    $project = Project::factory()->create(['group_id' => $group->id]);
    $expedition = Expedition::factory()->for($project)->create();
    ExpeditionStat::factory()->for($expedition, 'expedition')->create();
    $url = $route === 'admin.expeditions.create' ? route($route, $project) : route($route, $expedition);

    $first = selectionToken($this->actingAs($user)->get($url)->assertOk()->getContent());
    $second = selectionToken($this->actingAs($user)->get($url)->assertOk()->getContent());

    expect($first)->not->toBeNull()
        ->and($second)->not->toBe($first);
})->with(['admin.expeditions.create', 'admin.expeditions.edit', 'admin.expeditions.clone']);
