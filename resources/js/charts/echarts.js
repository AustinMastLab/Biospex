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
 * Apache ECharts with only the chart types and components the site uses, drawn as SVG.
 *
 * The charts are hidden from screen readers (aria-hidden on their container); each one has a data table with the
 * same numbers instead (see renderDataTable).
 */
import * as echarts from 'echarts/core';
import {BarChart, LineChart, MapChart, ScatterChart} from 'echarts/charts';
import {
    DataZoomComponent,
    GeoComponent,
    GridComponent,
    LegendComponent,
    TooltipComponent,
    VisualMapComponent,
} from 'echarts/components';
import {SVGRenderer} from 'echarts/renderers';

echarts.use([
    BarChart, LineChart, MapChart, ScatterChart,
    DataZoomComponent, GeoComponent, GridComponent, LegendComponent, TooltipComponent, VisualMapComponent,
    SVGRenderer,
]);

export {echarts};

export const numberFormat = new Intl.NumberFormat();

/**
 * Series colors, each at least 4:1 against the white background (WCAG 1.4.11 asks 3:1 for chart lines and areas),
 * ordered so neighbors differ in hue. amCharts' palette went as low as 2.2:1.
 */
export const palette = ['#1f6fb2', '#c4352b', '#2e7d32', '#7b4fb0', '#b35900', '#00737a', '#ad1457', '#5f6b00', '#3949ab',
    '#8c564b', '#0277bd', '#6a1b9a', '#558b2f', '#c62828', '#00695c', '#827717', '#b03a7a', '#37474f', '#6d4c41', '#4a4a4a'];

/**
 * Start a chart in the element, resizing with the window. Returns the existing chart if there is one.
 */
export function initChart(element) {
    const existing = echarts.getInstanceByDom(element);

    if (existing) {
        return existing;
    }

    const chart = echarts.init(element, null, {renderer: 'svg'});
    const resize = () => chart.resize();
    window.addEventListener('resize', resize);

    const dispose = chart.dispose.bind(chart);
    chart.dispose = () => {
        window.removeEventListener('resize', resize);
        dispose();
    };

    return chart;
}

/**
 * Fill a data table for screen readers (and anyone who opens it).
 *
 * @param {HTMLElement} host Element the table replaces the contents of
 * @param {string} caption
 * @param {string[]} headings
 * @param {Array<Array<string|number>>} rows Numbers are formatted and aligned right
 *
 * The table sits in the site's scrolling table area (.data-table-scroll): at most 22rem tall with its header row
 * kept in view, scrolling sideways when it is wider than the page, and focusable so it scrolls from the keyboard.
 */
export function renderDataTable(host, caption, headings, rows) {
    if (!host) {
        return;
    }

    const table = document.createElement('table');
    table.className = 'table table-sm data-table';
    table.createCaption().textContent = caption;

    const headRow = table.createTHead().insertRow();
    headings.forEach((heading, index) => {
        const th = document.createElement('th');
        th.scope = 'col';
        th.textContent = heading;
        if (index > 0) {
            th.className = 'text-right';
        }
        headRow.appendChild(th);
    });

    const body = table.createTBody();
    rows.forEach((row) => {
        const tr = body.insertRow();
        row.forEach((value, index) => {
            const cell = index === 0 ? document.createElement('th') : tr.insertCell();
            if (index === 0) {
                cell.scope = 'row';
                cell.className = 'font-weight-normal';
                tr.appendChild(cell);
            } else {
                cell.className = 'text-right';
            }
            cell.textContent = typeof value === 'number' ? numberFormat.format(value) : value;
        });
    });

    const wrapper = document.createElement('div');
    wrapper.className = 'data-table-scroll';
    wrapper.tabIndex = 0;
    wrapper.setAttribute('role', 'region');
    wrapper.setAttribute('aria-label', caption);
    wrapper.appendChild(table);

    host.replaceChildren(wrapper);
}
