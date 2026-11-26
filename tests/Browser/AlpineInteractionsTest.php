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
use App\Models\FaqCategory;

/**
 * Whether an element is shown, read in the page.
 */
function isShown(string $selector): string
{
    return "getComputedStyle(document.querySelector('{$selector}')).display !== 'none'";
}

it('opens a menu dropdown from its toggle, and closes it with Escape, returning focus to the toggle', function () {
    $page = visit('/faq');

    $page->assertScript(isShown('#dropdown00 + .dropdown-menu'), false)
        ->click('#dropdown00')
        ->assertScript(isShown('#dropdown00 + .dropdown-menu'), true)
        ->assertAttribute('#dropdown00', 'aria-expanded', 'true');

    $page->keys('#dropdown00', 'Escape')
        ->assertScript(isShown('#dropdown00 + .dropdown-menu'), false)
        ->assertAttribute('#dropdown00', 'aria-expanded', 'false')
        ->assertScript('document.activeElement.id', 'dropdown00')
        ->assertNoJavaScriptErrors();
});

it('shows one FAQ answer at a time', function () {
    $category = FaqCategory::factory()->create(['name' => 'General']);
    $first = Faq::factory()->for($category, 'faqCategory')->create(['question' => 'What is BIOSPEX?', 'answer' => 'A platform.']);
    $second = Faq::factory()->for($category, 'faqCategory')->create(['question' => 'Who runs it?', 'answer' => 'A team.']);

    $page = visit('/faq');

    $page->click("#faq-button-{$first->id}")
        ->wait(0.5)
        ->assertAttribute("#faq-button-{$first->id}", 'aria-expanded', 'true')
        ->assertScript(isShown("#collapse{$first->id}"), true);

    $page->click("#faq-button-{$second->id}")
        ->wait(0.5)
        ->assertAttribute("#faq-button-{$first->id}", 'aria-expanded', 'false')
        ->assertScript(isShown("#collapse{$first->id}"), false)
        ->assertScript(isShown("#collapse{$second->id}"), true)
        ->assertNoJavaScriptErrors();
});

it('shows a tooltip on hover and on keyboard focus, and hides it with Escape', function () {
    $page = visit('/faq');
    $page->script(<<<'JS'
        const link = document.createElement('a');
        link.href = '#';
        link.id = 'tip-target';
        link.dataset.hover = 'tooltip';
        link.title = 'Project public page';
        link.textContent = 'Project';
        document.querySelector('main, body').prepend(link);
    JS);

    $page->hover('#tip-target')
        ->assertScript("document.querySelector('[role=tooltip]').textContent", 'Project public page')
        ->assertScript("document.getElementById('tip-target').hasAttribute('title')", false)
        ->assertScript("document.getElementById(document.getElementById('tip-target').getAttribute('aria-describedby')) !== null", true);

    $page->keys('#tip-target', 'Escape')
        ->assertScript("document.querySelector('[role=tooltip]') === null", true);

    // Pressing Escape on the link focused it; leave and come back to focus it again.
    $page->script("document.getElementById('tip-target').blur(); document.getElementById('tip-target').focus()");
    $page->assertScript("document.querySelector('[role=tooltip]').textContent", 'Project public page')
        ->assertNoJavaScriptErrors();
});
