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

describe('expedition-logo-paths', function () {
    it('copies the medium logo to the flat path and updates logo_path', function () {
        Storage::fake('s3');
        Storage::disk('s3')->put('uploads/expeditions/logos/medium/12_Grass Sedges.jpg', 'medium-bytes');
        $expedition = Expedition::factory()->create([
            'logo_path' => 'uploads/expeditions/logos/original/12_Grass Sedges.jpg',
        ]);

        $this->artisan('app:update-queries', ['operation' => 'expedition-logo-paths'])->assertSuccessful();

        expect($expedition->fresh()->logo_path)->toBe('uploads/expeditions/logos/12_Grass Sedges.jpg');
        expect(Storage::disk('s3')->get('uploads/expeditions/logos/12_Grass Sedges.jpg'))->toBe('medium-bytes');
    });

    it('keeps an existing flat logo and still updates logo_path', function () {
        Storage::fake('s3');
        Storage::disk('s3')->put('uploads/expeditions/logos/medium/12_logo.jpg', 'medium-bytes');
        Storage::disk('s3')->put('uploads/expeditions/logos/12_logo.jpg', 'existing-bytes');
        $expedition = Expedition::factory()->create([
            'logo_path' => 'uploads/expeditions/logos/original/12_logo.jpg',
        ]);

        $this->artisan('app:update-queries', ['operation' => 'expedition-logo-paths'])->assertSuccessful();

        expect($expedition->fresh()->logo_path)->toBe('uploads/expeditions/logos/12_logo.jpg');
        expect(Storage::disk('s3')->get('uploads/expeditions/logos/12_logo.jpg'))->toBe('existing-bytes');
    });

    it('leaves logo_path unchanged when the medium logo is missing', function () {
        Storage::fake('s3');
        $expedition = Expedition::factory()->create([
            'logo_path' => 'uploads/expeditions/logos/original/12_logo.jpg',
        ]);

        $this->artisan('app:update-queries', ['operation' => 'expedition-logo-paths'])
            ->expectsOutputToContain('medium logo not found')
            ->assertSuccessful();

        expect($expedition->fresh()->logo_path)->toBe('uploads/expeditions/logos/original/12_logo.jpg');
        Storage::disk('s3')->assertMissing('uploads/expeditions/logos/12_logo.jpg');
    });

    it('ignores logos already in the flat layout and expeditions without a logo', function () {
        Storage::fake('s3');
        $migrated = Expedition::factory()->create(['logo_path' => 'uploads/expeditions/logos/12_logo.jpg']);
        $withoutLogo = Expedition::factory()->create(['logo_path' => null]);

        $this->artisan('app:update-queries', ['operation' => 'expedition-logo-paths'])
            ->expectsOutputToContain('0 paths updated')
            ->assertSuccessful();

        expect($migrated->fresh()->logo_path)->toBe('uploads/expeditions/logos/12_logo.jpg');
        expect($withoutLogo->fresh()->logo_path)->toBeNull();
    });
});
