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
 * Load amCharts 4 from its CDN on demand, for charts that only appear after a user action (the WeDigBio rate modal).
 * Pages that draw a chart when they load include common/amchart.blade.php instead.
 */
const base = 'https://www.amcharts.com/lib/4/';
const loading = {};

function loadScript(src) {
    loading[src] ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = () => reject(new Error(`Failed to load ${src}`));
        document.head.appendChild(script);
    });

    return loading[src];
}

/**
 * Load the amCharts core, charts and animated theme, in order. Resolves at once if they're already on the page.
 */
export async function loadAmCharts() {
    if (window.am4core && window.am4charts && window.am4themes_animated) {
        return;
    }

    await loadScript(base + 'core.js');
    await loadScript(base + 'charts.js');
    await loadScript(base + 'themes/animated.js');
}
