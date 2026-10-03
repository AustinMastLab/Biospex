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

it('succeeds without changes when no operation is given', function () {
    $this->artisan('app:update-queries')->assertSuccessful();
});

it('fails for an unknown operation', function () {
    $this->artisan('app:update-queries', ['operation' => 'not-an-operation'])->assertFailed();
});

it('accepts the force option passed by the deploy task', function () {
    $this->artisan('app:update-queries', ['--force' => true])->assertSuccessful();
});
