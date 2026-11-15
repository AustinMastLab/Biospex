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

it('stacks notifications at the top of the page and closes one with its button', function () {
    $page = visit('/projects');

    $page->script("notify('check-circle', 'First notice', 'success'); notify('exclamation-circle', 'Second notice', 'warning')");

    $page->assertSeeIn('#notify-stack', 'First notice')
        ->assertSeeIn('#notify-stack', 'Second notice')
        ->assertScript("document.querySelectorAll('#notify-stack .notify-alert').length", 2)
        ->assertScript("document.querySelector('#notify-stack .notify-alert').classList.contains('notify-success')", true);

    $page->click('#notify-stack .notify-alert:first-child .notify-close');

    $page->assertDontSeeIn('#notify-stack', 'First notice')
        ->assertSeeIn('#notify-stack', 'Second notice')
        ->assertNoJavaScriptErrors();
});

it('closes a notification by itself after six seconds', function () {
    $page = visit('/projects');

    $page->script("notify('info-circle', 'Short-lived notice', 'info')");
    $page->assertSeeIn('#notify-stack', 'Short-lived notice');
    $page->wait(6.5);

    $page->assertScript("document.querySelectorAll('#notify-stack .notify-alert').length", 0);
});

it('shows the message as text, not HTML', function () {
    $page = visit('/projects');

    $page->script("notify('info-circle', '<b>Bold</b> text', 'info')");

    $page->assertScript("document.querySelector('#notify-stack .notify-alert b')", null)
        ->assertSeeIn('#notify-stack', '<b>Bold</b> text');
});
