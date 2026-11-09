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
 * Open and close the <x-modal> dialogs (resources/views/components/modal.blade.php).
 *
 * - A click on [data-modal-target="#id"] opens that modal; the clicked element is the event's relatedTarget, so its
 *   data attributes (data-href, data-channel, ...) reach the modal's handlers as before.
 * - A click on [data-modal-dismiss] inside a modal, on the backdrop, or Escape closes it.
 * - window events open-modal / close-modal with detail {id} do the same, e.g. from Livewire:
 *   $this->dispatch('open-modal', id: 'ocr-modal').
 * - window.openModal(id, relatedTarget) and window.closeModal(id) do the same from scripts.
 *
 * Each modal dispatches modal:show (before it opens, detail {relatedTarget}), modal:shown (after the fade-in),
 * modal:hide and modal:hidden on its <dialog> element (they bubble), in place of Bootstrap's show.bs.modal ...
 * hidden.bs.modal.
 * The fade uses Bootstrap's .fade / .show classes and transition lengths.
 */

const FADE_MS = 300;

/**
 * Find a modal by id, with or without the leading #.
 */
function findModal(id) {
    const element = document.getElementById(String(id).replace(/^#/, ''));

    return element instanceof HTMLDialogElement ? element : null;
}

/**
 * Whether the user asked for less motion, in which case the fade is skipped.
 */
function reducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function emit(modal, name, detail = {}) {
    modal.dispatchEvent(new CustomEvent(name, {detail, bubbles: true}));
}

/**
 * Open a modal. relatedTarget is the element that opened it, if any.
 */
export function openModal(id, relatedTarget = null) {
    const modal = findModal(id);
    if (!modal) {
        return;
    }

    // Opened again while open (e.g. the global modal from a button inside it): close it, then open it for the new trigger.
    if (modal.open) {
        modal.addEventListener('modal:hidden', () => openModal(id, relatedTarget), {once: true});
        closeModal(id);

        return;
    }

    emit(modal, 'modal:show', {relatedTarget});

    modal.showModal();
    document.body.classList.add('modal-open');

    const fades = modal.classList.contains('fade') && !reducedMotion();
    // Add .show on the next frame so the fade-in transition runs.
    requestAnimationFrame(() => modal.classList.add('show'));
    setTimeout(() => emit(modal, 'modal:shown', {relatedTarget}), fades ? FADE_MS : 0);
}

/**
 * Close a modal, after the fade-out.
 */
export function closeModal(id) {
    const modal = findModal(id);
    if (!modal || !modal.open || modal.dataset.closing) {
        return;
    }

    emit(modal, 'modal:hide');
    modal.dataset.closing = '1';
    modal.classList.remove('show');

    const fades = modal.classList.contains('fade') && !reducedMotion();
    setTimeout(() => {
        delete modal.dataset.closing;
        modal.close();
    }, fades ? FADE_MS : 0);
}

window.openModal = openModal;
window.closeModal = closeModal;

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-modal-target]');
    if (opener) {
        event.preventDefault();
        openModal(opener.dataset.modalTarget, opener);

        return;
    }

    const dismiss = event.target.closest('[data-modal-dismiss]');
    const modal = event.target.closest('dialog.modal');
    if (dismiss && modal) {
        closeModal(modal.id);

        return;
    }

    // .modal-dialog lets clicks through (pointer-events: none), so a click outside .modal-content lands on the dialog.
    if (event.target instanceof HTMLDialogElement && event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
});

// Escape: fade out like the other ways of closing, instead of closing at once.
document.addEventListener('cancel', (event) => {
    if (event.target instanceof HTMLDialogElement && event.target.classList.contains('modal')) {
        event.preventDefault();
        closeModal(event.target.id);
    }
}, true);

document.addEventListener('close', (event) => {
    const modal = event.target;
    if (!(modal instanceof HTMLDialogElement) || !modal.classList.contains('modal')) {
        return;
    }

    delete modal.dataset.closing;
    modal.classList.remove('show');
    if (!document.querySelector('dialog.modal[open]')) {
        document.body.classList.remove('modal-open');
    }
    emit(modal, 'modal:hidden');
}, true);

window.addEventListener('open-modal', (event) => openModal(event.detail?.id));
window.addEventListener('close-modal', (event) => closeModal(event.detail?.id));
