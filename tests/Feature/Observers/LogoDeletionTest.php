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

use App\Jobs\DeleteGroupJob;
use App\Jobs\DeleteProjectJob;
use App\Models\Expedition;
use App\Models\Group;
use App\Models\Project;
use App\Services\MongoDbService;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\mock;

/**
 * MongoDB lives outside the test process, so the delete jobs get a stand-in.
 */
function fakeMongoDbService(): MongoDbService
{
    return mock(MongoDbService::class, function ($mock) {
        $mock->shouldReceive('setCollection', 'deleteMany');
    });
}

it('deletes the expedition logo when the expedition is deleted', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/expeditions/logos/1_logo.jpg', 'logo');
    $expedition = Expedition::factory()->create(['logo_path' => 'uploads/expeditions/logos/1_logo.jpg']);

    $expedition->delete();

    Storage::disk('s3')->assertMissing('uploads/expeditions/logos/1_logo.jpg');
});

it('deletes the project logo when the project is deleted', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/projects/logos/1_logo.png', 'logo');
    $project = Project::factory()->create(['logo_path' => 'uploads/projects/logos/1_logo.png']);

    $project->delete();

    Storage::disk('s3')->assertMissing('uploads/projects/logos/1_logo.png');
});

it('deletes records without a logo and leaves other files alone', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/other.jpg', 'other');
    $expedition = Expedition::factory()->create(['logo_path' => null]);
    $project = Project::factory()->create(['logo_path' => null]);

    $expedition->delete();
    $project->delete();

    $this->assertModelMissing($expedition);
    $this->assertModelMissing($project);
    Storage::disk('s3')->assertExists('uploads/other.jpg');
});

it('still deletes the record when removing the logo from S3 fails', function () {
    $expedition = Expedition::factory()->create(['logo_path' => 'uploads/expeditions/logos/1_logo.jpg']);
    Storage::shouldReceive('disk')->with('s3')->andThrow(new RuntimeException('S3 unavailable'));

    $expedition->delete();

    $this->assertModelMissing($expedition);
});

it('deletes the project and expedition logos when DeleteProjectJob runs', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/projects/logos/1_project.png', 'logo');
    Storage::disk('s3')->put('uploads/expeditions/logos/1_expedition.jpg', 'logo');
    $project = Project::factory()->create(['logo_path' => 'uploads/projects/logos/1_project.png']);
    Expedition::factory()->create(['project_id' => $project->id, 'logo_path' => 'uploads/expeditions/logos/1_expedition.jpg']);

    (new DeleteProjectJob($project))->handle(fakeMongoDbService());

    Storage::disk('s3')->assertMissing('uploads/projects/logos/1_project.png');
    Storage::disk('s3')->assertMissing('uploads/expeditions/logos/1_expedition.jpg');
});

it('deletes project and expedition logos when DeleteGroupJob runs', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/projects/logos/1_project.png', 'logo');
    Storage::disk('s3')->put('uploads/expeditions/logos/1_expedition.jpg', 'logo');
    $group = Group::factory()->create();
    $project = Project::factory()->create(['group_id' => $group->id, 'logo_path' => 'uploads/projects/logos/1_project.png']);
    Expedition::factory()->create(['project_id' => $project->id, 'logo_path' => 'uploads/expeditions/logos/1_expedition.jpg']);

    (new DeleteGroupJob($group))->handle(fakeMongoDbService());

    $this->assertModelMissing($project);
    Storage::disk('s3')->assertMissing('uploads/projects/logos/1_project.png');
    Storage::disk('s3')->assertMissing('uploads/expeditions/logos/1_expedition.jpg');
});
