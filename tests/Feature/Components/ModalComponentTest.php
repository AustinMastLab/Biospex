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

it('renders a dialog with the Bootstrap modal markup, title and an Exit button', function () {
    $view = $this->blade('<x-modal id="rate-modal" title="Rate Chart" size="modal-xl">Chart</x-modal>');

    $view->assertSee('<dialog id="rate-modal" aria-labelledby="rate-modal-title" class="modal fade">', false)
        ->assertSee('<div class="modal-dialog modal-dialog-centered modal-xl">', false)
        ->assertSee('<h2 id="rate-modal-title" class="color-action">Rate Chart</h2>', false)
        ->assertSeeInOrder(['Chart', 'data-modal-dismiss>Exit</button>'], false);
});

it('leaves out the header when there is no title', function () {
    $view = $this->blade('<x-modal id="loading-modal">Loading</x-modal>');

    $view->assertDontSee('modal-header', false)
        ->assertDontSee('aria-labelledby', false);
});

it('replaces the Exit button with the footer slot', function () {
    $view = $this->blade('<x-modal id="winner-modal" title="WINNER"><x-slot:footer><button>EXIT</button></x-slot:footer></x-modal>');

    $view->assertSee('<button>EXIT</button>', false)
        ->assertDontSee('>Exit</button>', false);
});

it('marks a modal without a backdrop', function () {
    $view = $this->blade('<x-modal id="bingo-modal" title="WINNER" :backdrop="false"/>');

    $view->assertSee('class="modal fade modal-no-backdrop"', false);
});
