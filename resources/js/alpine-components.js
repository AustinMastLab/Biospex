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
 * Alpine components shared by the public and admin pages. Alpine itself comes with Livewire's scripts
 * (@livewireScripts in both layouts), which start it after these are registered.
 */
document.addEventListener('alpine:init', () => {
    /**
     * Countdown to a date (resources/views/components/countdown.blade.php).
     *
     * @param {string} deadline A date the browser can parse, e.g. "Sun Sep 30 2018 14:26:26 GMT-0400"
     */
    window.Alpine.data('countdown', (deadline) => ({
        days: '0',
        hours: '00',
        minutes: '00',
        seconds: '00',
        timer: null,

        init() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },

        destroy() {
            clearInterval(this.timer);
        },

        tick() {
            const remaining = Math.max(0, Date.parse(deadline) - Date.now());
            const pad = (value) => String(value).padStart(2, '0');

            this.days = String(Math.floor(remaining / 86400000));
            this.hours = pad(Math.floor(remaining / 3600000) % 24);
            this.minutes = pad(Math.floor(remaining / 60000) % 60);
            this.seconds = pad(Math.floor(remaining / 1000) % 60);

            if (remaining === 0) {
                clearInterval(this.timer);
            }
        },
    }));
});
