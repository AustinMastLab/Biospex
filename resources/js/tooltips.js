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
 * Tooltips for elements with data-hover="tooltip": their title, shown in Bootstrap's tooltip style (CSS only).
 *
 * One set of document listeners serves every such element, including those in HTML loaded into modals and in
 * Livewire components, so nothing has to be set up again when content changes.
 *
 * WCAG 1.4.13: shown on hover and on keyboard focus; stays while the pointer is over the tooltip; Escape hides it.
 * data-html="true" titles (a div that only left-aligns the text) are shown as left-aligned text.
 */
const selector = '[data-hover="tooltip"]';
let current = null;

/**
 * The element's tooltip text. The title moves to data-tooltip-title so the browser's own tooltip doesn't show too.
 */
function tooltipText(element) {
    if (element.hasAttribute('title')) {
        element.dataset.tooltipTitle = element.getAttribute('title');
        element.removeAttribute('title');
    }

    const title = element.dataset.tooltipTitle || '';

    return element.dataset.html === 'true'
        ? new DOMParser().parseFromString(title, 'text/html').body.textContent.trim()
        : title;
}

function hide() {
    if (!current) {
        return;
    }

    current.element.removeAttribute('aria-describedby');
    current.tip.remove();
    current = null;
}

function show(element) {
    if (current?.element === element) {
        return;
    }

    hide();

    const text = tooltipText(element);
    if (text === '') {
        return;
    }

    const tip = document.createElement('div');
    tip.id = 'tooltip-' + Math.random().toString(36).slice(2);
    tip.className = 'tooltip bs-tooltip-top show';
    tip.setAttribute('role', 'tooltip');

    const arrow = document.createElement('div');
    arrow.className = 'arrow';
    const inner = document.createElement('div');
    inner.className = 'tooltip-inner' + (element.dataset.html === 'true' ? ' text-left' : '');
    inner.textContent = text;
    tip.append(arrow, inner);
    document.body.appendChild(tip);

    // Above the element, or below it when there's no room above; kept inside the window.
    const box = element.getBoundingClientRect();
    const tipBox = tip.getBoundingClientRect();
    const above = box.top - tipBox.height >= 0;
    tip.classList.toggle('bs-tooltip-top', above);
    tip.classList.toggle('bs-tooltip-bottom', !above);

    const left = Math.min(Math.max(4, box.left + box.width / 2 - tipBox.width / 2), window.innerWidth - tipBox.width - 4);
    tip.style.position = 'absolute';
    tip.style.top = `${window.scrollY + (above ? box.top - tipBox.height : box.bottom)}px`;
    tip.style.left = `${window.scrollX + left}px`;
    arrow.style.left = `${Math.min(Math.max(6, box.left + box.width / 2 - left - 6), tipBox.width - 18)}px`;

    element.setAttribute('aria-describedby', tip.id);
    current = {element, tip};
}

document.addEventListener('mouseover', (event) => {
    const element = event.target.closest(selector);
    if (element) {
        show(element);
    } else if (current && !current.tip.contains(event.target)) {
        hide();
    }
});

document.addEventListener('focusin', (event) => {
    const element = event.target.closest(selector);
    element ? show(element) : hide();
});

document.addEventListener('focusout', (event) => {
    if (current && event.target.closest(selector) === current.element) {
        hide();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        hide();
    }
});

// The element may be removed or the page scrolled while the tooltip shows.
window.addEventListener('scroll', hide, {passive: true});
document.addEventListener('livewire:navigated', hide);
