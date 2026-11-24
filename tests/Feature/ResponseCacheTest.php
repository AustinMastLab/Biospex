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

use App\Models\Faq;
use App\Models\OcrQueue;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;

beforeEach(function () {
    config([
        'responsecache.enabled' => true,
        'responsecache.cache.store' => 'array',
        'responsecache.cache.tag' => 'biospex-tag',
        'responsecache.debug.enabled' => true,
    ]);
});

it('serves public pages from the response cache', function () {
    $this->get(route('front.faqs.index'))->assertOk()->assertHeader('X-Cache-Status', 'MISS');

    $this->get(route('front.faqs.index'))->assertOk()->assertHeader('X-Cache-Status', 'HIT');
});

it('never caches admin pages', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('admin.projects.index'))->assertOk()->assertHeaderMissing('X-Cache-Status');
    $this->actingAs($user)->get(route('admin.projects.index'))->assertOk()->assertHeaderMissing('X-Cache-Status');
});

it('clears the cached pages when public content changes', function () {
    $faq = Faq::factory()->create();
    $this->get(route('front.faqs.index'))->assertOk();

    $faq->update(['question' => 'An edited question?']);

    $this->get(route('front.faqs.index'))
        ->assertHeader('X-Cache-Status', 'MISS')
        ->assertSee('An edited question?');
});

it('keeps the cached pages when only pipeline data changes', function () {
    $ocrQueue = OcrQueue::factory()->create();
    $this->get(route('front.faqs.index'))->assertOk();

    $ocrQueue->update(['total' => 99]);

    $this->get(route('front.faqs.index'))->assertHeader('X-Cache-Status', 'HIT');
});

it('clears only the cached pages, not the other cached values in the same store', function () {
    Cache::store('array')->put('project_state_counts:1', ['FL' => 5]);
    $this->get(route('front.faqs.index'))->assertOk();

    ResponseCache::clear();

    expect(Cache::store('array')->get('project_state_counts:1'))->toBe(['FL' => 5]);
    $this->get(route('front.faqs.index'))->assertHeader('X-Cache-Status', 'MISS');
});
