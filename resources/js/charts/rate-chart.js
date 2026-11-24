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
 * Rate charts (event teams and WeDigBio projects): estimated records per hour over time, one line per team or project.
 *
 * Rows from the server: [{ date: 'yyyy-MM-dd HH:mm:ss', 'Team A': 12, 'Team B': 3 }, ...], a point every 5 minutes.
 * Times are shown as given (the event's time zone, or UTC for WeDigBio), as the axis title says.
 *
 * Zooming (WCAG 2.1.1 and 2.5.7): the mouse wheel zooms and dragging pans, and buttons above the chart (Zoom in, Zoom
 * out, Earlier, Later, Show all) do the same from the keyboard or with a single click. A status line says which time
 * range is shown. The view is kept when the chart is redrawn with new points.
 */
import {echarts, initChart, numberFormat, palette, renderDataTable} from './echarts.js';

const echartsInstance = (element) => echarts.getInstanceByDom(element);

/**
 * The server sends a list, or an object keyed by interval when it fills an empty range.
 */
export function rowsFrom(data) {
    return Array.isArray(data) ? data : Object.values(data ?? {});
}

function toTime(date) {
    return new Date(String(date).replace(' ', 'T')).getTime();
}

/**
 * Milliseconds until the next 5-minute mark, when the server has a new point.
 */
export function untilNextPoint() {
    const step = 5 * 60 * 1000;

    return Math.ceil(Date.now() / step) * step - Date.now();
}

/**
 * Draw or redraw the chart.
 *
 * @param {HTMLElement} element
 * @param {object} options
 * @param {string[]} options.names Team or project names (the row keys)
 * @param {object[]} options.rows
 * @param {string} options.timeTitle Axis title naming the time zone
 * @param {string} options.nameHeading Data table heading for the names
 * @param {HTMLElement|null} options.table Where the data table goes
 * @param {'all'|'last-day'} [options.startView] What the chart first shows: the whole range, or its last 24 hours
 */
export function drawRateChart(element, {names, rows, timeTitle, nameHeading, table, startView = 'all'}) {
    const firstDraw = echartsInstance(element) === undefined;
    const chart = initChart(element);
    const legendRows = Math.ceil(names.length / (element.clientWidth >= 900 ? 3 : 1));
    element.style.height = `${440 + legendRows * 24}px`;
    chart.resize();

    const series = names.map((name) => {
        const points = rows
            .filter((row) => row[name] !== undefined && row[name] !== null)
            .map((row) => [toTime(row.date), Number(row[name])]);

        // The latest point is marked, as before.
        if (points.length > 0) {
            points[points.length - 1] = {value: points[points.length - 1], symbol: 'circle', symbolSize: 10};
        }

        return {name, points};
    });

    const times = rows.map((row) => toTime(row.date)).filter((time) => !Number.isNaN(time));
    const extent = times.length > 0 ? [Math.min(...times), Math.max(...times)] : [0, 0];
    const zoom = controlsFor(element, chart);
    zoom.extent = extent;

    if (firstDraw) {
        zoom.view = startingView(extent, startView);
    }

    chart.setOption({
        color: palette,
        animation: false,
        tooltip: {
            trigger: 'axis',
            valueFormatter: (value) => numberFormat.format(Math.round(value)),
        },
        legend: {
            bottom: 0,
            left: 'center',
            width: '95%',
            icon: 'circle',
            textStyle: {fontWeight: 'bold'},
        },
        grid: {left: 80, right: 30, top: 20, bottom: 70 + legendRows * 24},
        xAxis: {
            type: 'time',
            name: timeTitle,
            nameLocation: 'middle',
            nameGap: 36,
            nameTextStyle: {fontSize: 16, fontWeight: 600},
        },
        yAxis: {
            type: 'value',
            name: 'Estimated Records Per Hour',
            nameLocation: 'middle',
            nameGap: 56,
            nameTextStyle: {fontSize: 16},
            axisLabel: {formatter: (value) => numberFormat.format(value)},
        },
        // Mouse wheel zooms and dragging pans; the buttons do the same without them.
        dataZoom: [{
            type: 'inside',
            xAxisIndex: 0,
            filterMode: 'none',
            startValue: zoom.view[0],
            endValue: zoom.view[1],
            minValueSpan: minimumSpan,
        }],
        series: series.map(({name, points}) => ({
            name,
            type: 'line',
            smooth: 0.3,
            symbol: 'none',
            sampling: 'lttb',
            lineStyle: {width: 2},
            data: points,
        })),
    }, {notMerge: true});

    zoom.update();

    renderDataTable(table, `Estimated records per hour, each hour's average (${timeTitle})`, ['Hour', ...names], hourlyRows(names, rows));

    return chart;
}

const hourFormat = new Intl.DateTimeFormat(undefined, {month: 'short', day: 'numeric', hour: 'numeric'});

/**
 * The chart's points as one row per hour: each team's or project's average rate in that hour, rounded.
 */
function hourlyRows(names, rows) {
    const hours = new Map();

    rows.forEach((row) => {
        const time = toTime(row.date);
        if (Number.isNaN(time)) {
            return;
        }

        const hour = new Date(time);
        hour.setMinutes(0, 0, 0);
        const key = hour.getTime();

        if (!hours.has(key)) {
            hours.set(key, names.map(() => ({sum: 0, count: 0})));
        }

        hours.get(key).forEach((total, index) => {
            const value = row[names[index]];
            if (value !== undefined && value !== null) {
                total.sum += Number(value);
                total.count += 1;
            }
        });
    });

    return [...hours.entries()]
        .sort(([a], [b]) => a - b)
        .map(([hour, totals]) => [
            hourFormat.format(hour),
            ...totals.map((total) => (total.count > 0 ? Math.round(total.sum / total.count) : '')),
        ]);
}

/**
 * The smallest range zooming in reaches: one hour.
 */
const minimumSpan = 60 * 60 * 1000;

const rangeFormat = new Intl.DateTimeFormat(undefined, {month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit'});

/**
 * 'last-day' starts on the last 24 hours while the event is running (its data reaches the present), as the WeDigBio
 * chart did; its times are UTC shown as they are, so the present is read the same way.
 */
function startingView(extent, startView) {
    const day = 24 * 60 * 60 * 1000;
    const nowOnAxis = Date.now() + new Date().getTimezoneOffset() * 60 * 1000;
    const isRunning = Math.abs(nowOnAxis - extent[1]) < 30 * 60 * 1000;

    if (startView === 'last-day' && isRunning && extent[1] - extent[0] > day) {
        return [extent[1] - day, extent[1]];
    }

    return [...extent];
}

/**
 * The zoom buttons and status line above the chart, made once per chart element and kept across redraws.
 */
function controlsFor(element, chart) {
    if (element.rateZoom) {
        // The modal makes a new chart each time it opens.
        if (element.rateZoom.chart !== chart) {
            element.rateZoom.chart = chart;
            chart.on('datazoom', element.rateZoom.followChart);
        }

        return element.rateZoom;
    }

    const toolbar = document.createElement('div');
    toolbar.className = 'd-flex flex-wrap align-items-center mb-2';
    toolbar.style.gap = '.5rem';
    toolbar.setAttribute('role', 'group');
    toolbar.setAttribute('aria-label', 'Chart time range');

    // A button that can't act is marked aria-disabled rather than disabled, so it stays in the tab order (and keeps
    // focus when pressing it reaches the limit, e.g. Zoom out reaching the whole range).
    const button = (label, action) => {
        const control = document.createElement('button');
        control.type = 'button';
        control.className = 'btn btn-primary btn-sm';
        control.textContent = label;
        control.addEventListener('click', () => {
            if (control.getAttribute('aria-disabled') !== 'true') {
                action();
            }
        });
        toolbar.appendChild(control);

        return control;
    };
    const setAvailable = (control, available) => {
        control.setAttribute('aria-disabled', available ? 'false' : 'true');
        control.classList.toggle('disabled', !available);
    };

    const status = document.createElement('p');
    status.className = 'small mb-2';
    status.setAttribute('role', 'status');

    const zoom = {
        chart,
        extent: [0, 0],
        view: [0, 0],

        /**
         * Show a range, kept inside the data and at least an hour long.
         */
        show([start, end]) {
            const [min, max] = this.extent;
            const span = Math.min(Math.max(end - start, minimumSpan), max - min);
            start = Math.min(Math.max(start, min), max - span);
            this.view = [start, start + span];
            this.chart.dispatchAction({type: 'dataZoom', startValue: this.view[0], endValue: this.view[1]});
            this.update();
        },

        update() {
            const [min, max] = this.extent;
            const [start, end] = this.view;
            const span = end - start;
            const showingAll = start <= min && end >= max;
            setAvailable(zoomIn, span > minimumSpan);
            setAvailable(zoomOut, !showingAll);
            setAvailable(showAll, !showingAll);
            setAvailable(earlier, start > min);
            setAvailable(later, end < max);
            status.textContent = max > min
                ? `Showing ${rangeFormat.format(start)} to ${rangeFormat.format(end)}.`
                : 'No data yet.';
        },
    };

    const center = () => (zoom.view[0] + zoom.view[1]) / 2;
    const span = () => zoom.view[1] - zoom.view[0];
    const zoomIn = button('Zoom in', () => zoom.show([center() - span() / 4, center() + span() / 4]));
    const zoomOut = button('Zoom out', () => zoom.show([center() - span(), center() + span()]));
    const earlier = button('Earlier', () => zoom.show([zoom.view[0] - span() / 2, zoom.view[1] - span() / 2]));
    const later = button('Later', () => zoom.show([zoom.view[0] + span() / 2, zoom.view[1] + span() / 2]));
    const showAll = button('Show all', () => zoom.show([...zoom.extent]));

    // Zooming with the mouse wheel or dragging updates the buttons and the status line.
    zoom.followChart = () => {
        const [option] = zoom.chart.getOption().dataZoom;
        if (option?.startValue !== undefined && option?.endValue !== undefined) {
            zoom.view = [Number(option.startValue), Number(option.endValue)];
            zoom.update();
        }
    };
    chart.on('datazoom', zoom.followChart);

    element.before(toolbar, status);
    element.rateZoom = zoom;

    return zoom;
}
