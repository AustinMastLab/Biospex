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

// Ensure the global Laravel object exists on every page that loads this bundle.
window.Laravel = window.Laravel || {};

// Links with .prevent-default only trigger scripts (modals, confirmations); don't follow their href.
document.addEventListener('click', (event) => {
    if (event.target.closest('.prevent-default')) {
        event.preventDefault();
    }
});

// Open links in a configured popup window (used across front/admin).
// Falls back to target="_blank" if window.open is blocked.
document.addEventListener('click', (event) => {
    const link = event.target.closest('a.js-popup-window');
    if (!link) {
        return;
    }

    const width = Number(link.dataset.popupWidth) || 700;
    const height = Number(link.dataset.popupHeight) || 800;

    // Try to center the popup on screen
    const left = Math.max(0, Math.floor((window.screen.width - width) / 2));
    const top = Math.max(0, Math.floor((window.screen.height - height) / 2));

    const popup = window.open(link.href, link.dataset.popupName || 'popup', `width=${width},height=${height},left=${left},top=${top}`);

    // If the popup was created, prevent the normal navigation.
    // If it was blocked, allow the browser to follow the link (target="_blank").
    if (popup) {
        event.preventDefault();
        popup.focus();
    }
});

// Flash messages, once the page and notify() (below) are ready.
document.addEventListener('DOMContentLoaded', () => {
    const flashCookie = document.cookie.split('; ').find(row => row.startsWith('app_flash='));
    if (flashCookie) {
        try {
            const rawValue = decodeURIComponent(flashCookie.split('=')[1]);
            const data = JSON.parse(rawValue);

            if (data && data.message) {
                notify(data.icon, data.message, data.type);
            }

            // Force delete by matching the path and ensuring no domain conflict
            document.cookie = "app_flash=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "app_flash=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + window.location.hostname + ";";
            document.cookie = "app_flash=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=." + window.location.hostname + ";";

            const domainSegments = window.location.hostname.split('.');
            for (let index = 1; index < domainSegments.length - 1; index++) {
                const parentDomain = domainSegments.slice(index).join('.');
                document.cookie = "app_flash=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=." + parentDomain + ";";
            }
        } catch (e) {
            console.error("Flash cookie error", e);
        }
    }

    if (Laravel.flashMessage && Laravel.flashMessage.length) {
        notify(Laravel.flashIcon, Laravel.flashMessage, Laravel.flashType);
    }
});

// The scoreboard, WeDigBio progress and event export below still use jQuery (#664).
$(function () {
    // Add token to any ajax requests.
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let scoreboardChannel = null;
    $('#scoreboard-modal').on('modal:show', function (e) {
        let $modal = $(this).find('.modal-body');
        let $button = $(e.originalEvent.detail.relatedTarget); // Button that triggered the modal
        let channel = $button.data('channel');
        let eventId = $button.data('event');
        scoreboardChannel = channel;

        $modal.html('<div class="loader mx-auto"></div>');

        $modal.load($button.data('href'), function () {
            // Closed before the scoreboard arrived: don't fill it or start listening.
            if (!$modal.closest('dialog').prop('open')) {
                $modal.html('');
                return;
            }

            Echo.channel(channel)
                .listen('ScoreboardEvent', (e) => {
                    $.each(e.data, function (id, val) {
                        if (Number(id) === Number(eventId)) {
                            $modal.html(val);
                        }
                    });
                });
        });
    }).on('modal:hidden', function () {
        $(this).find('.modal-body').html('');
        // Stop the listener this opening added, so reopening doesn't stack them.
        if (scoreboardChannel) {
            Echo.channel(scoreboardChannel).stopListening('ScoreboardEvent');
            scoreboardChannel = null;
        }
    });

    // Used in Admin but placed in common.js because it calls notify function.
    $('.event-export').on('click', function () {
        let url = $(this).data('href');
        let successMsg = $(this).data('success');
        let errorMsg = $(this).data('error');
        notify('info-circle', 'Request is being sent.', 'info');
        $.get(url, function (data) {
            let icon = data === true ? 'check-circle' : 'times-circle';
            let msg = data === true ? successMsg : errorMsg;
            let type = data === true ? 'success' : 'danger';
            notify(icon, msg, type);
        });
    });

    let progressChannel = null;
    $('#wedigbio-progress-modal').on('modal:show', function (e) {
        let $modal = $(this).find('.modal-body');
        let $button = $(e.originalEvent.detail.relatedTarget); // Button that triggered the modal

        let channel = $button.data('channel');
        progressChannel = channel;
        let uuid = $button.data('uuid');

        $modal.html('<div class="loader mx-auto"></div>');

        $modal.load($button.data('href'), function () {
            // Closed before the progress arrived: don't fill it or start listening.
            if (!$modal.closest('dialog').prop('open')) {
                $modal.html('');
                return;
            }

            let deadline = $modal.find('#inProgress').html(); // Sun Sep 30 2018 14:26:26 GMT-0400 (Eastern Daylight Time)
            if (deadline === null) {
                return;
            }
            // UUID is matched with the event uuid from WeDigBioEventProgressJob
            // WeDigBioEventProgressJob called from WeDigBioTranscriptionService
            Echo.channel(channel)
                .listen('WeDigBioProgressEvent', (e) => {
                    $.each(e.data, function (id, val) {
                        if (id === uuid) {
                            $modal.html(val);
                        }
                    });
                });
        });
    }).on('modal:hidden', function () {
        $(this).find('.modal-body').html('');
        if (progressChannel) {
            Echo.channel(progressChannel).stopListening('WeDigBioProgressEvent');
            progressChannel = null;
        }
    });
});

/**
 * Show a notification at the top centre of the page for six seconds (dismissible with ×).
 * Notifications stack below each other; styles are in components/_notifications.scss. The message is shown as text.
 *
 * @param {string} icon Font Awesome icon name without the "fa-" prefix, e.g. "check-circle"
 * @param {string} msg  The message
 * @param {string} type success, info, warning or danger
 */
window.notify = function (icon, msg, type) {
    let stack = document.getElementById('notify-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'notify-stack';
        stack.className = 'notify-stack';
        document.body.appendChild(stack);
    }

    const alert = document.createElement('div');
    alert.className = 'notify-alert notify-' + type;
    alert.setAttribute('role', 'alert');

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'notify-close';
    close.setAttribute('aria-label', 'Close');
    close.textContent = '×';

    const iconSpan = document.createElement('span');
    iconSpan.className = 'fas fa-' + icon + ' fa-2x';
    iconSpan.setAttribute('aria-hidden', 'true');

    const message = document.createElement('span');
    message.textContent = msg;

    alert.append(close, iconSpan, ' ', message);
    stack.appendChild(alert);

    const dismiss = () => alert.remove();
    close.addEventListener('click', dismiss);
    setTimeout(dismiss, 6000);
}
