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
 * The WeDigBio rate chart, drawn when its modal opens and again every 5 minutes while it is open.
 *
 * The modal is in the menu of every page, so the chart code (and ECharts) loads only the first time it opens.
 */
const modal = document.getElementById('wedigbio-rate-modal');

if (modal) {
    const element = document.getElementById('weDigBioRateChartDiv');
    const table = document.getElementById('weDigBioRateTable');
    let timer = null;
    let chart = null;
    let opened = 0; // Which opening of the modal a load belongs to, so a load finishing after it closes is dropped.

    function showMessage(message) {
        chart?.dispose();
        chart = null;
        element.style.height = 'auto';
        element.innerHTML = `<p class="text-center">${message}</p>`;
        table.replaceChildren();
    }

    modal.addEventListener('modal:show', (event) => {
        const trigger = event.detail.relatedTarget;
        const opening = ++opened;

        async function load() {
            try {
                const [{drawRateChart, rowsFrom}, names] = await Promise.all([
                    import('./rate-chart.js'),
                    fetch(trigger.dataset.projects, {headers: {Accept: 'application/json'}}).then((response) => response.json()),
                ]);

                if (opening !== opened) {
                    return;
                }

                if (!Array.isArray(names)) {
                    showMessage('No current WeDigBio Event');

                    return;
                }

                if (names.length === 0) {
                    showMessage('No digitizations yet');

                    return;
                }

                const data = await fetch(trigger.dataset.href, {headers: {Accept: 'application/json'}}).then((response) => response.json());

                if (opening !== opened) {
                    return;
                }

                if (chart === null) {
                    element.replaceChildren();
                }

                chart = drawRateChart(element, {names, rows: rowsFrom(data), timeTitle: 'UTC Timezone', nameHeading: 'Project', table, startView: 'last-day'});
            } catch {
                if (opening === opened) {
                    showMessage('Failed to load the chart');
                }
            }
        }

        load();
        timer = setInterval(load, 5 * 60 * 1000);
    });

    modal.addEventListener('modal:hidden', () => {
        opened++;
        clearInterval(timer);
        chart?.dispose();
        chart = null;
        element.replaceChildren();
        table.replaceChildren();
    });
}
