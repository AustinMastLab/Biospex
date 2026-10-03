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

use App\Models\Project;
use Illuminate\Support\Facades\Storage;

it('returns the stored project logo url without checking that the file exists', function () {
    Storage::fake('s3');
    $project = Project::factory()->make(['logo_path' => 'uploads/projects/logos/12_logo.png']);

    expect($project->present()->show_logo)
        ->toBe(Storage::disk('s3')->url('uploads/projects/logos/12_logo.png'));
});

it('returns the project placeholder when no logo is stored', function (?string $logoPath) {
    $project = Project::factory()->make(['logo_path' => $logoPath]);

    expect($project->present()->show_logo)->toBe(config('config.missing_project_logo'));
})->with([null, '']);
