<?php

/*
 * Copyright (C) 2015  Biospex
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

use App\Models\SiteAsset;

describe('SiteAsset Page Basic Tests', function () {
    it('displays the site-asset page successfully', function () {
        $response = $this->get(route('front.site-assets.index'));

        $response->assertStatus(200);
    });

    it('returns the correct view for site-asset page', function () {
        $response = $this->get(route('front.site-assets.index'));

        $response->assertViewIs('front.site-asset.index');
    });
});

describe('SiteAsset Content Tests', function () {
    it('displays site-asset titles when available', function () {
        Storage::fake('s3');
        SiteAsset::factory()->count(3)->create();
        $titles = SiteAsset::all()->pluck('title')->toArray();

        foreach (SiteAsset::all() as $asset) {
            Storage::disk('s3')->put($asset->download_path, 'fake content');
        }

        $response = $this->get(route('front.site-assets.index'));

        foreach ($titles as $title) {
            $response->assertSee($title);
        }
    });

    it('displays site-asset content when available', function () {
        Storage::fake('s3');
        $resources = SiteAsset::factory()->count(2)->create();

        foreach ($resources as $resource) {
            Storage::disk('s3')->put($resource->download_path, 'fake content');
        }

        $response = $this->get(route('front.site-assets.index'));

        foreach ($resources as $resource) {
            $response->assertSee($resource->title);
            if ($resource->description) {
                $response->assertSee($resource->description);
            }
        }
    });
});
