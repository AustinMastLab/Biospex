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
 * Ask for confirmation before following links and buttons marked data-confirm="confirmation".
 *
 * The dialog is the <x-confirm-dialog> component in the admin layout: data-title and data-content are its title and
 * message (shown as text by Alpine), data-method is the request method (GET navigates, anything else submits a form
 * with _method and the CSRF token), and the URL comes from data-href or href.
 */
const dialog = document.getElementById('confirm-dialog');
let pendingAction = null;

/**
 * Create a hidden input for the method-spoofing form.
 */
function hiddenInput(name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;

    return input;
}

/**
 * Follow the confirmed link: navigate for GET, otherwise submit a POST form with _method and the CSRF token.
 */
function runAction({url, method}) {
    if (method === 'get') {
        window.location.assign(url);

        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.hidden = true;
    form.append(
        hiddenInput('_method', method),
        hiddenInput('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '')
    );
    document.body.appendChild(form);
    form.submit();
}

if (dialog) {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm="confirmation"]');
        if (!trigger) {
            return;
        }

        event.preventDefault();

        pendingAction = {
            url: trigger.dataset.href ?? trigger.getAttribute('href'),
            method: (trigger.dataset.method || 'get').toLowerCase(),
        };

        // Bootstrap 4 modals pull focus back into themselves, so a dialog opened from one has to live inside it.
        (document.querySelector('.modal.show') ?? document.body).appendChild(dialog);

        window.dispatchEvent(new CustomEvent('confirm-dialog-open', {
            detail: {title: trigger.dataset.title ?? '', message: trigger.dataset.content ?? ''},
        }));

        dialog.returnValue = '';
        dialog.showModal();
    });

    // A click on the backdrop (the dialog element itself, outside its content) cancels.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close('cancel');
        }
    });

    // Closing by either button, Escape or the backdrop ends here; only the Confirm button runs the action.
    dialog.addEventListener('close', () => {
        const action = pendingAction;
        pendingAction = null;
        document.body.appendChild(dialog);

        if (dialog.returnValue === 'confirm' && action) {
            runAction(action);
        }
    });
}
