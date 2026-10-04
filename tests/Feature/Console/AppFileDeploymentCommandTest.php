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

use Illuminate\Support\Facades\Storage;

/**
 * Collect the distinct directory= values written to the rendered Supervisor configs.
 *
 * @return array<int, string>
 */
function renderedSupervisorDirectories(): array
{
    return collect(Storage::files('supervisor'))
        ->flatMap(fn (string $file) => preg_match_all('/^directory=(.*)$/m', Storage::get($file), $matches) ? $matches[1] : [])
        ->unique()
        ->values()
        ->all();
}

it('runs Supervisor programs from the path given by --current-path', function () {
    Storage::fake();

    $this->artisan('app:deploy-files', ['--current-path' => '/data/web/biospex/current/'])->assertSuccessful();

    expect(renderedSupervisorDirectories())->toBe(['/data/web/biospex/current']);
});

it('runs Supervisor programs from the application base path by default', function () {
    Storage::fake();

    $this->artisan('app:deploy-files')->assertSuccessful();

    expect(renderedSupervisorDirectories())->toBe([base_path()]);
});

it('leaves no APP_CURRENT_PATH placeholder in the rendered configs', function () {
    Storage::fake();

    $this->artisan('app:deploy-files', ['--current-path' => '/data/web/biospex/current'])->assertSuccessful();

    expect(collect(Storage::files('supervisor'))->contains(fn (string $file) => str_contains(Storage::get($file), 'APP_CURRENT_PATH')))->toBeFalse();
});
