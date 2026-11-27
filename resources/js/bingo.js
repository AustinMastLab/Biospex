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

/**
 * The bingo card page around the card (App\Livewire\BingoCard): the map of where players are, and the winner message.
 *
 * The card announces with browser events: bingo-player-joined (another player's map pin) and bingo-won (this player,
 * or another, won). When the winner message closes, the card deals a new card (bingo-new-card).
 */
import {createBingoMap} from './charts/bingo-map.js';

const mapReady = createBingoMap(document.getElementById('bingodiv'), JSON.parse(Laravel.bingoUserData), {
    list: document.getElementById('bingo-players'),
    count: document.getElementById('bingo-players-count'),
});

window.addEventListener('bingo-player-joined', (event) => {
    mapReady.then((map) => map.addPlayer(event.detail.marker));
});

const modal = document.getElementById('bingo-modal');
const confetti = document.getElementById('bingo-conffeti');
let closeTimer = null;

window.addEventListener('bingo-won', (event) => {
    const body = modal.querySelector('.modal-body');
    const heading = document.createElement('h3');
    heading.textContent = event.detail.message;
    body.replaceChildren(heading);
    confetti.hidden = false;

    openModal('bingo-modal');

    // The message closes by itself after ten seconds (or with Exit).
    clearTimeout(closeTimer);
    closeTimer = setTimeout(() => closeModal('bingo-modal'), 10000);
});

modal.addEventListener('modal:hidden', () => {
    clearTimeout(closeTimer);
    confetti.hidden = true;
    modal.querySelector('.modal-body').replaceChildren();
    window.Livewire.dispatch('bingo-new-card');
});
