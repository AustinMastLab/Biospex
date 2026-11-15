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
use App\Models\Project;
use App\Models\Workflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

it('succeeds without changes when no operation is given', function () {
    $this->artisan('app:update-queries')->assertSuccessful();
});

it('fails for an unknown operation', function () {
    $this->artisan('app:update-queries', ['operation' => 'not-an-operation'])->assertFailed();
});

it('accepts the force option passed by the deploy task', function () {
    $this->artisan('app:update-queries', ['--force' => true])->assertSuccessful();
});

it('unlocks expeditions that were locked without changing to GeoLocate', function () {
    $geoLocateWorkflow = Workflow::factory()->create();
    config(['geolocate.workflow_id' => $geoLocateWorkflow->id]);
    $lockedByMistake = Expedition::factory()->create(['locked' => 1, 'updated_at' => '2026-01-02 03:04:05']);
    $onGeoLocate = Expedition::factory()->create(['locked' => 1, 'workflow_id' => $geoLocateWorkflow->id]);
    $unlocked = Expedition::factory()->create(['locked' => 0]);

    $this->artisan('app:update-queries', ['operation' => 'unlock-expeditions', '--force' => true])
        ->expectsOutput('Unlocked 1 expeditions.')
        ->assertSuccessful();

    expect($lockedByMistake->fresh())
        ->locked->toBe(0)
        ->updated_at->toDateTimeString()->toBe('2026-01-02 03:04:05')
        ->and($onGeoLocate->fresh()->locked)->toBe(1)
        ->and($unlocked->fresh()->locked)->toBe(0);
});

it('cleans project descriptions and saves the originals first', function () {
    Storage::fake('local');
    $styled = Project::factory()->create(['updated_at' => '2026-01-02 03:04:05']);
    $clean = Project::factory()->create();
    $original = '<p style="font-size:18px"><span style="color:red">Wading</span> through <b>water</b> plants</p><style>p{}</style>';
    // Saved the way older descriptions were, before the model cleaned them.
    DB::table('projects')->where('id', $styled->id)->update(['description_long' => $original]);
    DB::table('projects')->where('id', $clean->id)->update(['description_long' => '<p>Already clean</p>']);

    $this->artisan('app:update-queries', ['operation' => 'clean-project-descriptions', '--force' => true])
        ->expectsOutputToContain('Cleaned 1 project descriptions.')
        ->assertSuccessful();

    expect(DB::table('projects')->where('id', $styled->id)->value('description_long'))->toBe('<p>Wading through <b>water</b> plants</p>')
        ->and($styled->fresh()->updated_at->toDateTimeString())->toBe('2026-01-02 03:04:05')
        ->and(DB::table('projects')->where('id', $clean->id)->value('description_long'))->toBe('<p>Already clean</p>');
    $backups = Storage::disk('local')->files('backups');
    expect($backups)->toHaveCount(1)
        ->and(json_decode(Storage::disk('local')->get($backups[0]), true))->toBe([(string) $styled->id => $original]);
});

it('changes nothing and writes no backup when the descriptions are already clean', function () {
    Storage::fake('local');
    $project = Project::factory()->create();
    DB::table('projects')->where('id', $project->id)->update(['description_long' => '<p>Already clean</p>']);

    $this->artisan('app:update-queries', ['operation' => 'clean-project-descriptions', '--force' => true])
        ->expectsOutput('Cleaned 0 project descriptions.')
        ->assertSuccessful();

    expect(Storage::disk('local')->files('backups'))->toBeEmpty();
});
