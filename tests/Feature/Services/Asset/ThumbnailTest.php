<?php

/*
 * Copyright (C) 2014 - 2025, Biospex
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

use App\Services\Asset\Thumbnail;
use App\Services\Requests\HttpRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

use function Pest\Laravel\mock;

it('generates a resized jpg thumbnail from a downloaded image', function () {
    Storage::fake('public');
    config([
        'config.thumb_output_dir' => 'thumbs',
        'config.thumb_width' => 40,
        'config.thumb_height' => 30,
    ]);

    $url = 'https://images.example.org/specimen.png';
    $sourceImage = (string) app(ImageManager::class)->createImage(200, 100)->encodeUsingPath('specimen.png');
    $mockHandler = new MockHandler([new Response(200, [], $sourceImage)]);

    mock(HttpRequest::class)
        ->shouldReceive('createDirectHttpClient')
        ->once()
        ->andReturn(new Client(['handler' => HandlerStack::create($mockHandler)]));

    $thumbnail = app(Thumbnail::class)->getThumbnail($url);

    $thumbPath = 'thumbs/40_30/'.md5($url).'.jpg';
    Storage::disk('public')->assertExists($thumbPath);
    expect($thumbnail)->toBe(Storage::disk('public')->get($thumbPath));
    expect((string) $mockHandler->getLastRequest()->getUri())->toBe($url);

    $generated = app(ImageManager::class)->decode($thumbnail);
    expect($generated->width())->toBe(40)
        ->and($generated->height())->toBe(30)
        ->and($generated->origin()->mediaType())->toBe('image/jpeg');
});
