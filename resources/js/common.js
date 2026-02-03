/*
 * Copyright (C) 2014 - 2025, Biospex
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

$(function () {
    // Add token to any ajax requests.
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Fix for Bootstrap modal aria-hidden accessibility issue
    // Enhanced fix to handle all dismissal methods and dynamic content
    let modalObserver;

    // Aggressive prevention: Override setAttribute to prevent aria-hidden on modals with focus
    const originalSetAttribute = Element.prototype.setAttribute;
    Element.prototype.setAttribute = function (name, value) {
        if (name === 'aria-hidden' && value === 'true' && this.classList.contains('modal')) {
            const focusedElement = this.querySelector(':focus');
            const modalHasFocus = this.contains(document.activeElement);

            if (focusedElement || modalHasFocus) {
                return; // Block the setAttribute call completely
            }
        }
        return originalSetAttribute.call(this, name, value);
    };

    // Initialize MutationObserver for comprehensive modal monitoring
    if (window.MutationObserver) {
        modalObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'aria-hidden') {
                    const modal = mutation.target;
                    if (modal.classList.contains('modal') && modal.getAttribute('aria-hidden') === 'true') {
                        const focusedElement = modal.querySelector(':focus');
                        const modalHasFocus = modal.contains(document.activeElement);

                        if (focusedElement || modalHasFocus) {
                            // Remove aria-hidden immediately if modal contains focused elements
                            modal.removeAttribute('aria-hidden');
                        }
                    }
                }
            });
        });

        // Function to start observing a modal
        function observeModal(modal) {
            modalObserver.observe(modal, {
                attributes: true,
                attributeFilter: ['aria-hidden']
            });
        }

        // Observe existing modals
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.modal').forEach(observeModal);
        });

        // Also observe modals that might be created dynamically
        $(document).ready(function () {
            $('.modal').each(function () {
                observeModal(this);
            });
        });
    }

    // Multi-layer approach: handle different dismissal timing scenarios
    $(document).on('hide.bs.modal', '.modal', function () {
        const modal = this;

        // Check immediately for focused elements
        const checkAndRemoveAriaHidden = () => {
            const focusedElement = modal.querySelector(':focus');
            const ariaHidden = modal.getAttribute('aria-hidden');

            if (focusedElement && ariaHidden === 'true') {
                modal.removeAttribute('aria-hidden');
            }
        };

        // Multiple timing checks to handle different dismissal scenarios
        checkAndRemoveAriaHidden(); // Immediate check
        setTimeout(checkAndRemoveAriaHidden, 0); // Next tick
        setTimeout(checkAndRemoveAriaHidden, 1); // Slight delay for backdrop clicks
        setTimeout(checkAndRemoveAriaHidden, 10); // Additional safety net
        setTimeout(checkAndRemoveAriaHidden, 50); // Extended delay for backdrop clicks
        setTimeout(checkAndRemoveAriaHidden, 100); // Additional safety for slower systems
        setTimeout(checkAndRemoveAriaHidden, 200); // Even longer delay for backdrop clicks
        setTimeout(checkAndRemoveAriaHidden, 500); // Maximum delay for slow systems
    });

    // Additional event handler specifically for backdrop clicks
    // Bootstrap triggers these events in different order for backdrop vs button clicks
    $(document).on('hidden.bs.modal', '.modal', function () {
        const modal = this;

        // Final check after modal is fully hidden
        const finalCheck = () => {
            const ariaHidden = modal.getAttribute('aria-hidden');
            const focusedElement = modal.querySelector(':focus');

            if (ariaHidden === 'true') {
                if (focusedElement) {
                    modal.removeAttribute('aria-hidden');
                } else {
                    // Check if modal itself or any descendant might still have focus or be focusable
                    const modalHasFocus = modal.contains(document.activeElement);
                    const focusableElements = modal.querySelectorAll('input, textarea, select, button, a[href], [tabindex]:not([tabindex="-1"])');

                    if (modalHasFocus || focusableElements.length > 0) {
                        modal.removeAttribute('aria-hidden');
                    }
                }
            }
        };

        // Check immediately and with extended delays to catch backdrop dismissals
        finalCheck();
        setTimeout(finalCheck, 0);
        setTimeout(finalCheck, 1);
        setTimeout(finalCheck, 10);
        setTimeout(finalCheck, 50);
        setTimeout(finalCheck, 100);
        setTimeout(finalCheck, 200);
        setTimeout(finalCheck, 500);
    });

    // Set prevent default for links
    $(document).on('click', '.prevent-default', function (e) {
        e.preventDefault();
    });

    // Tooltips
    $('[data-hover="tooltip"]').tooltip();
    $(document).ajaxComplete(function () {
        $('[data-hover="tooltip"]').tooltip();
    });

    $(".hamburger").click(function () {
        $(this).toggleClass("is-active");
    });

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
                // Extra safety: Try to delete the dotted domain version too if it exists
                document.cookie = "app_flash=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + window.location.hostname + ";";
                document.cookie = "app_flash=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=." + window.location.hostname + ";";
            } catch (e) {
                console.error("Flash cookie error", e);
            }
        }

    if (Laravel.flashMessage && Laravel.flashMessage.length) {
        notify(Laravel.flashIcon, Laravel.flashMessage, Laravel.flashType);
    }


    $('.sort-page').on('click', function () {
        sortPage($(this));
    });

    $('.toggle-view-btn').on('click', function () {
        let html = $(this).html();
        let value = $(this).data('value');
        $(this).html(value);
        $(this).data('value', html);
    });

    $('#scoreboard-modal').on('show.bs.modal', function (e) {
        let $modal = $(this).find('.modal-body');
        let $button = $(e.relatedTarget); // Button that triggered the modal
        let channel = $button.data('channel');
        let eventId = $button.data('event');

        $modal.html('<div class="loader mx-auto"></div>');

        $modal.load($button.data('href'), function () {
            let $clock = $modal.find('.clockdiv');
            let deadline = $modal.find('#date').html(); // Sun Sep 30 2018 14:26:26 GMT-0400 (Eastern Daylight Time)
            if (deadline === null) {
                return;
            }
            initializeClock($clock, deadline);

            Echo.channel(channel)
                .listen('ScoreboardEvent', (e) => {
                    $.each(e.data, function (id, val) {
                        if (Number(id) === Number(eventId)) {
                            $modal.html(val);
                            $clock = $modal.find('.clockdiv');
                            deadline = $modal.find('#date').html();
                            initializeClock($clock, deadline);
                        }
                    });
                });
        });
    }).on('hidden.bs.modal', function () {
        $(this).find('.modal-body').html('');
        clearInterval(timeInterval);
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

    clockDiv();

    $('#wedigbio-progress-modal').on('show.bs.modal', function (e) {
        let $modal = $(this).find('.modal-body');
        let $button = $(e.relatedTarget); // Button that triggered the modal

        let channel = $button.data('channel');
        let uuid = $button.data('uuid');

        $modal.html('<div class="loader mx-auto"></div>');

        $modal.load($button.data('href'), function () {
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
    }).on('hidden.bs.modal', function () {
        $(this).find('.modal-body').html('');
        clearInterval(timeInterval);
    });
});

/**
 * data attributes: project-id, type (active, completed), sort, order, url, target
 * @param element
 */
sortPage = function (element) {
    const data = element.data();
    const $target = $('#' + data.target); // target container

    $target.html('<span class="loader"></span>');

    $.post(data.url, data)
        .done(function (response) {
            $target.html(response);
            setOrder(data.order, element);
            clockDiv();
        });
}

setOrder = function (order, element) {
    const $icon = element.find('i');
    element.siblings('span').data('order', 'asc').find('i').removeClass().addClass('fas fa-sort');

    switch (order) {
        case 'asc':
            element.data('order', 'desc');
            $icon.removeClass().addClass('fas fa-sort-up');
            break;
        case 'desc':
            element.data('order', '');
            $icon.removeClass().addClass('fas fa-sort-down');
            break;
        default:
            element.data('order', 'asc');
            $icon.removeClass().addClass('fas fa-sort');
            break;
    }
}

let timeInterval;

getTimeRemaining = function (endTime) {
    const now = Date.now();
    const t = Date.parse(endTime) - now;
    const seconds = Math.floor((t / 1000) % 60);
    const minutes = Math.floor((t / 1000 / 60) % 60);
    const hours = Math.floor((t / (1000 * 60 * 60)) % 24);
    const days = Math.floor(t / (1000 * 60 * 60 * 24));
    return {
        'total': t,
        'days': days,
        'hours': hours,
        'minutes': minutes,
        'seconds': seconds
    };
}

clockDiv = function () {
    $('.clockdiv').each(function () {
        const $this = $(this);
        const deadline = $this.data('value'); // Sun Sep 30 2018 14:26:26 GMT-0400 (Eastern Daylight Time)
        initializeClock($this, deadline);
    });
}

initializeClock = function ($clock, endTime) {

    const daysSpan = $clock.find('.days');
    const hoursSpan = $clock.find('.hours');
    const minutesSpan = $clock.find('.minutes');
    const secondsSpan = $clock.find('.seconds');

    function updateClock() {
        const t = getTimeRemaining(endTime);
        daysSpan.html(t.days);
        hoursSpan.html(('0' + t.hours).slice(-2));
        minutesSpan.html(('0' + t.minutes).slice(-2));
        secondsSpan.html(('0' + t.seconds).slice(-2));

        if (t.total <= 0) {
            clearInterval(timeInterval);
        }
    }

    updateClock();
    timeInterval = setInterval(updateClock, 1000);
}

notify = function (icon, msg, type) {
    $.notify({
        icon: 'fas fa-' + icon + ' fa-2x',
        message: msg
    }, {
        type: type,
        allow_dismiss: true,
        placement: {
            from: "top",
            align: "center"
        },
        offset: 5,
        spacing: 10,
        animate: {
            enter: "animate__animated animate__fadeInDown",
            exit: "animate__animated animate__fadeOutUp"
        },
        delay: 6000,
    });
}
