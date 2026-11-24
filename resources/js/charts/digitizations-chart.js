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
 * The project page's Digitizations chart: a year's running totals per expedition, stacked.
 *
 * Data (front.projects.digitizations): { year, dates: ['2024-01-01', ...],
 *   series: [{ expeditionId, name, total, values: [running total per day] }] }
 */
import {initChart, numberFormat, palette, renderDataTable} from './echarts.js';

const element = document.getElementById('digitizations-chart');

if (element) {
    const chart = initChart(element);
    const buttons = document.querySelectorAll('[data-digitizations-year]');

    function draw(data) {
        // Long expedition names fit about two to a line on a wide chart and one on a narrow one.
        const perLine = element.clientWidth >= 900 ? 2 : 1;
        const legendRows = Math.ceil(data.series.length / perLine);
        element.style.height = `${420 + legendRows * 24}px`;
        chart.resize();

        chart.setOption({
            color: palette,
            animationDuration: 600,
            tooltip: {
                trigger: 'axis',
                axisPointer: {type: 'line', lineStyle: {color: '#8F3985', width: 2}},
                valueFormatter: (value) => numberFormat.format(value),
            },
            legend: {
                bottom: 0,
                left: 'center',
                width: '95%',
                itemGap: 12,
                icon: 'rect',
                formatter: (name) => {
                    const series = data.series.find((item) => item.name === name);

                    return series ? `${name}  ${numberFormat.format(series.total)}` : name;
                },
            },
            grid: {left: 60, right: 24, top: 24, bottom: 40 + legendRows * 24},
            xAxis: {type: 'category', boundaryGap: false, data: data.dates},
            yAxis: {type: 'value', axisLabel: {formatter: (value) => numberFormat.format(value)}},
            series: data.series.map((series) => ({
                name: series.name,
                type: 'line',
                stack: 'digitizations',
                areaStyle: {opacity: 1},
                lineStyle: {width: 1},
                showSymbol: false,
                emphasis: {focus: 'series'},
                data: series.values,
            })),
        }, {notMerge: true});

        renderDataTable(
            document.getElementById('digitizations-table'),
            `Digitizations in ${data.year}: each expedition's running total at the end of each month`,
            ['Month', ...data.series.map((series) => series.name)],
            monthlyRows(data),
        );
    }

    const monthFormat = new Intl.DateTimeFormat(undefined, {month: 'long', year: 'numeric', timeZone: 'UTC'});

    /**
     * The running totals the chart draws, at the last day of each month shown.
     */
    function monthlyRows(data) {
        const lastDayOfMonth = new Map();
        data.dates.forEach((date, day) => lastDayOfMonth.set(date.slice(0, 7), day));

        return [...lastDayOfMonth.entries()].map(([month, day]) => [
            monthFormat.format(new Date(`${month}-01T00:00:00Z`)),
            ...data.series.map((series) => series.values[day]),
        ]);
    }

    async function load(year) {
        chart.showLoading();

        try {
            const response = await fetch(`${element.dataset.url}/${year}`, {headers: {Accept: 'application/json'}});
            draw(await response.json());
        } finally {
            chart.hideLoading();
        }
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            buttons.forEach((other) => {
                const selected = other === button;
                other.classList.toggle('btn-transcription-year', selected);
                other.classList.toggle('btn-primary', !selected);
                other.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            load(button.dataset.digitizationsYear);
        });
    });

    buttons[0]?.click();
}
