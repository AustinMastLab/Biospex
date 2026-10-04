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
use App\Models\Profile;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

it('keeps referenced logos and deletes unreferenced ones', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/expeditions/logos/1_used.jpg', 'x');
    Storage::disk('s3')->put('uploads/expeditions/logos/2_orphan.jpg', 'x');
    Storage::disk('s3')->put('uploads/projects/logos/1_used.png', 'x');
    Storage::disk('s3')->put('uploads/projects/logos/2_orphan.png', 'x');
    Expedition::factory()->create(['logo_path' => 'uploads/expeditions/logos/1_used.jpg']);
    Project::factory()->create(['logo_path' => 'uploads/projects/logos/1_used.png']);
    $this->travel(2)->days();

    $this->artisan('files:cleanup-orphaned')->assertSuccessful();

    Storage::disk('s3')->assertExists(['uploads/expeditions/logos/1_used.jpg', 'uploads/projects/logos/1_used.png']);
    Storage::disk('s3')->assertMissing(['uploads/expeditions/logos/2_orphan.jpg', 'uploads/projects/logos/2_orphan.png']);
});

it('keeps the medium and small variants of a referenced avatar', function () {
    Storage::fake('s3');
    foreach (['original', 'medium', 'small'] as $size) {
        Storage::disk('s3')->put("uploads/profiles/avatars/{$size}/1_me.jpg", 'x');
        Storage::disk('s3')->put("uploads/profiles/avatars/{$size}/2_gone.jpg", 'x');
    }
    Profile::factory()->create(['avatar_path' => 'uploads/profiles/avatars/original/1_me.jpg']);
    $this->travel(2)->days();

    $this->artisan('files:cleanup-orphaned')->assertSuccessful();

    foreach (['original', 'medium', 'small'] as $size) {
        Storage::disk('s3')->assertExists("uploads/profiles/avatars/{$size}/1_me.jpg");
        Storage::disk('s3')->assertMissing("uploads/profiles/avatars/{$size}/2_gone.jpg");
    }
});

it('removes files left in retired expedition logo subdirectories', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/expeditions/logos/1_logo.jpg', 'x');
    Storage::disk('s3')->put('uploads/expeditions/logos/original/1_logo.jpg', 'x');
    Storage::disk('s3')->put('uploads/expeditions/logos/medium/1_logo.jpg', 'x');
    Expedition::factory()->create(['logo_path' => 'uploads/expeditions/logos/1_logo.jpg']);
    $this->travel(2)->days();

    $this->artisan('files:cleanup-orphaned')->assertSuccessful();

    Storage::disk('s3')->assertExists('uploads/expeditions/logos/1_logo.jpg');
    Storage::disk('s3')->assertMissing(['uploads/expeditions/logos/original/1_logo.jpg', 'uploads/expeditions/logos/medium/1_logo.jpg']);
});

it('keeps orphaned files newer than the cutoff', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/expeditions/logos/2_just_uploaded.jpg', 'x');

    $this->artisan('files:cleanup-orphaned')
        ->expectsOutputToContain('Skipping recent file')
        ->assertSuccessful();

    Storage::disk('s3')->assertExists('uploads/expeditions/logos/2_just_uploaded.jpg');
});

it('deletes nothing on a dry run', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/expeditions/logos/2_orphan.jpg', 'x');
    $this->travel(2)->days();

    $this->artisan('files:cleanup-orphaned', ['--dry-run' => true])
        ->expectsOutputToContain('[DRY RUN] Would delete: uploads/expeditions/logos/2_orphan.jpg')
        ->assertSuccessful();

    Storage::disk('s3')->assertExists('uploads/expeditions/logos/2_orphan.jpg');
});

it('fails instead of reporting a clean result when a directory cannot be listed', function () {
    Storage::shouldReceive('disk')->with('s3')->andThrow(new RuntimeException('AccessDenied'));

    $this->artisan('files:cleanup-orphaned', ['--dry-run' => true])
        ->expectsOutputToContain('Error processing directory')
        ->assertFailed();
});

it('leaves hidden placeholder files alone', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/expeditions/logos/.gitkeep', '');
    $this->travel(2)->days();

    $this->artisan('files:cleanup-orphaned')->assertSuccessful();

    Storage::disk('s3')->assertExists('uploads/expeditions/logos/.gitkeep');
});
