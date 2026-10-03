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

use App\Services\Asset\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores an expedition logo as a single 636x416 image in the logos directory', function () {
    Storage::fake('s3');
    $upload = UploadedFile::fake()->image('wide logo.jpg', 1200, 300);

    $path = app(ImageUploadService::class)->uploadImage($upload, 'Expedition', 'uploads/expeditions/logos');

    expect($path)->toStartWith('uploads/expeditions/logos/')
        ->and($path)->toEndWith('_wide logo.jpg')
        ->and(Storage::disk('s3')->allFiles('uploads/expeditions/logos'))->toBe([$path]);

    [$width, $height] = getimagesizefromstring(Storage::disk('s3')->get($path));
    expect([$width, $height])->toBe([636, 416]);
});

it('deletes the previous expedition logo when replacing it', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('uploads/expeditions/logos/1_old.jpg', 'old');
    $upload = UploadedFile::fake()->image('new.jpg', 800, 600);

    app(ImageUploadService::class)->uploadImage($upload, 'Expedition', 'uploads/expeditions/logos', 'uploads/expeditions/logos/1_old.jpg');

    Storage::disk('s3')->assertMissing('uploads/expeditions/logos/1_old.jpg');
});
