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

use App\Models\Faq;
use App\Models\FaqCategory;

describe('FAQ Page Basic Tests', function () {
    it('displays the FAQ page successfully', function () {
        $response = $this->get(route('front.faqs.index'));

        $response->assertStatus(200);
    });

    it('returns the correct view for FAQ page', function () {
        $response = $this->get(route('front.faqs.index'));

        $response->assertViewIs('front.faq.index');
    });
});

describe('FAQ Content Tests', function () {
    it('displays FAQ categories and questions when available', function () {
        FaqCategory::factory()->count(3)
            ->has(Faq::factory()->count(2))
            ->create();

        $response = $this->get(route('front.faqs.index'));

        $names = FaqCategory::all()->pluck('name')->toArray();

        foreach ($names as $name) {
            $response->assertSee($name);
        }

        $response->assertStatus(200);
    });

    it('displays FAQ questions from categories', function () {
        $category = FaqCategory::factory()
            ->has(Faq::factory()->count(2), 'faqs')
            ->create();

        $response = $this->get(route('front.faqs.index'));

        foreach ($category->faqs as $faq) {
            $response->assertSee($faq->question);
        }
    });
});
