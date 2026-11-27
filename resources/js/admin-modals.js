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
 * The global modal (common/modal.blade.php): a trigger with data-modal-target="#global-modal" and data-url loads that
 * page into the modal's body, with its data-title as the heading and data-size (modal-lg, modal-xl) as the width.
 *
 * Forms in the loaded bodies are Livewire components (app/Livewire/Admin), which Livewire starts when they are added to
 * the page; they show their own notices and close the modal when done.
 */
const modal = document.getElementById('global-modal');

if (modal) {
    const title = document.getElementById('modal-title');
    const body = modal.querySelector('.modal-body');
    const dialog = modal.querySelector('.modal-dialog');
    let size = '';
    let request = null;

    const reset = () => {
        request?.abort();
        request = null;
        if (size) {
            dialog.classList.remove(size);
            size = '';
        }
        body.replaceChildren();
        title.textContent = 'Dialog';
    };

    modal.addEventListener('modal:show', async (event) => {
        const trigger = event.detail.relatedTarget;
        if (!trigger?.dataset.url) {
            return;
        }

        reset();
        size = trigger.dataset.size || '';
        if (size) {
            dialog.classList.add(size);
        }
        // Never an empty heading (accessibility checkers can scan the modal at any time).
        title.textContent = (trigger.dataset.title || '').trim() || 'Dialog';
        body.innerHTML = '<div class="loader mx-auto"></div>';

        const controller = new AbortController();
        request = controller;

        try {
            // X-Requested-With: the modal controllers answer only requests made from the page (Request::ajax()).
            const response = await fetch(trigger.dataset.url, {
                headers: {'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html, application/json'},
                signal: controller.signal,
            });
            const isJson = (response.headers.get('content-type') || '').includes('application/json');
            if (!response.ok || isJson) {
                const data = isJson ? await response.json() : {};
                throw new Error(data.message || 'This could not be loaded. Please try again.');
            }
            body.innerHTML = await response.text();
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            closeModal('global-modal');
            notify('exclamation-circle', error.message, 'warning');
        }
    });

    modal.addEventListener('modal:hidden', reset);
}
