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
 * An event's rate chart, drawn when its modal opens. While open, the new 5-minute points are added as the server
 * has them: the request after the first ends with the time of the previous load (event.get.rate's timestamp).
 */
import {drawRateChart, rowsFrom, untilNextPoint} from './rate-chart.js';

const modal = document.getElementById('step-chart-modal');

if (modal) {
    const element = document.getElementById('chartdiv');
    const table = document.getElementById('event-rate-chart-table');
    let timer = null;
    let chart = null;
    let opened = 0; // Which opening of the modal a load belongs to, so a load finishing after it closes is dropped.

    modal.addEventListener('modal:show', (event) => {
        const trigger = event.detail.relatedTarget;
        const names = trigger.dataset.teams.split(',');
        const url = trigger.dataset.href;
        const timeTitle = trigger.dataset.timezone;
        const rowsByDate = new Map();
        const opening = ++opened;
        let lastLoad = null;

        async function load() {
            const requestUrl = lastLoad === null ? url : `${url}/${lastLoad.getTime()}`;
            lastLoad = new Date();
            const response = await fetch(requestUrl, {headers: {Accept: 'application/json'}});
            const data = await response.json();

            if (opening !== opened) {
                return;
            }

            rowsFrom(data).forEach((row) => rowsByDate.set(row.date, row));

            const rows = [...rowsByDate.values()].sort((a, b) => String(a.date).localeCompare(String(b.date)));
            chart = drawRateChart(element, {names, rows, timeTitle, nameHeading: 'Team', table});
        }

        function scheduleNext(delay) {
            if (opening !== opened) {
                return;
            }

            timer = setTimeout(async () => {
                await load();
                scheduleNext(5 * 60 * 1000);
            }, delay);
        }

        load().then(() => scheduleNext(untilNextPoint()));
    });

    modal.addEventListener('modal:hidden', () => {
        opened++;
        clearTimeout(timer);
        chart?.dispose();
        chart = null;
    });
}
