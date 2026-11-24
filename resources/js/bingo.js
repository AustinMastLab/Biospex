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

import {createBingoMap} from './charts/bingo-map.js';

$(function () {

    // Where the players are; a player who joins is added as they arrive (BingoEvent below).
    const mapReady = createBingoMap(document.getElementById('bingodiv'), JSON.parse(Laravel.bingoUserData), {
        list: document.getElementById('bingo-players'),
        count: document.getElementById('bingo-players-count'),
    });

    Echo.channel(Laravel.channel)
        .listen('BingoEvent', (e) => {
            let data = JSON.parse(e.data);
            let winner = data.winner;
            let marker = data.marker;

            if (marker['uuid'] === Laravel.bingoUserUuid) {
                return;
            }

            mapReady.then((map) => map.addPlayer(marker));

            if (winner === null) {
                return;
            }

            showWinnerModal($bingoRows, '<h3>We Have A Winner In ' + winner.city + '!!</h3>');
        });

    let $bingoRows = $('#bingo-rows');
    createRows($bingoRows);

    // Set winning combinations to array
    let winners = [['a1', 'a2', 'a3', 'a4', 'a5'], ['b1', 'b2', 'b3', 'b4', 'b5'], ['c1', 'c2', 'c3', 'c4', 'c5'], ['d1', 'd2', 'd3', 'd4', 'd5'], ['e1', 'e2', 'e3', 'e4', 'e5'], ['a1', 'b1', 'c1', 'd1', 'e1'], ['a2', 'b2', 'c2', 'd2', 'e2'], ['a3', 'b3', 'c3', 'd3', 'e3'], ['a4', 'b4', 'c4', 'd4', 'e4'], ['a5', 'b5', 'c5', 'd5', 'e5'], ['a1', 'b2', 'c3', 'd4', 'e5'], ['a5', 'b4', 'c3', 'd2', 'e1']];
    let possibleWinners = winners.length;

    // Initialize selected array with c3 freebie
    let selected = ['c3'];

    // Toggle clicked and not clicked
    $bingoRows.on('click', '.square', function () {
        const $cell = $(this);

        // Skip the logo square
        if ($cell.hasClass('logo')) {
            return;
        }

        $cell.toggleClass('clicked');

        // Keep aria-pressed in sync for screen readers
        $cell.attr('aria-pressed', $cell.hasClass('clicked') ? 'true' : 'false');

        // Push clicked object ID to 'selected' array
        selected.push($cell.attr('id'));

        // Compare winners array to selected array for matches
        for (let i = 0; i < possibleWinners; i++) {
            let cellExists = 0;

            for (let j = 0; j < 5; j++) {
                if ($.inArray(winners[i][j], selected) > -1) {
                    cellExists++;
                }
            }

            // If all 5 winner cells exist in selected array alert success message
            if (cellExists === 5) {
                $.get(Laravel.winnerUrl);
                showWinnerModal($bingoRows, '<h3>You are a winner!! Congratulations!!</h3>', true);
                selected = ['c3'];
            }
        }
    });

    // Keyboard support: Enter / Space toggles the focused cell
    $bingoRows.on('keydown', '.square', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') {
            return;
        }

        e.preventDefault();
        $(this).trigger('click');
    });

});

function showWinnerModal($bingoRows, msg, owner = false) {
    const modal = document.getElementById('bingo-modal');
    const $body = $(modal).find('.modal-body');

    modal.addEventListener('modal:shown', function () {
        $('#bingo-conffeti').collapse('show');
        $body.html(msg);

        setTimeout(function () {
            closeModal('bingo-modal');
            $('#bingo-conffeti').collapse('hide');
            $body.html('');
        }, 10000);
    }, {once: true});

    modal.addEventListener('modal:hidden', function () {
        createRows($bingoRows);
        $('#bingo-conffeti').collapse('hide');
    }, {once: true});

    openModal('bingo-modal');
}

function createRows($bingoRows) {
    $bingoRows.html('');
    $.post(Laravel.rowsUrl)
        .done(function (data) {
            $bingoRows.html(data);

            // After rows are injected, set up focusability in DOM order
            initBingoCellTabOrder($bingoRows);
        })
        .fail(function () {
            $bingoRows.html('<p>Failed to load bingo rows.</p>');
        });
}

function initBingoCellTabOrder($bingoRows) {
    // Make ONLY the playable cells tabbable, in DOM creation order.
    // (DOM order == the order they appear in the injected HTML.)
    const $cells = $bingoRows.find('.square').not('.logo');

    $cells.each(function () {
        const $cell = $(this);

        // Make div behave like a button for a11y
        $cell.attr('tabindex', '0');
        $cell.attr('role', 'button');

        // Keep aria-pressed aligned with state
        $cell.attr('aria-pressed', $cell.hasClass('clicked') ? 'true' : 'false');
    });

    // Ensure the logo is not tabbable
    $bingoRows.find('.square.logo').removeAttr('tabindex').removeAttr('role').removeAttr('aria-pressed');
}
