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
use App\Models\Workflow;

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
