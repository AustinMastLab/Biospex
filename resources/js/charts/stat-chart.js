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
 * The project statistics page's Digitizations chart: how many participants made each number of digitizations.
 *
 * Data (Laravel.transcriptions): [{ transcriptions: 12, transcribers: 30 }, ...]
 */
import {initChart, numberFormat, palette, renderDataTable} from './echarts.js';

const element = document.getElementById('statDiv');

if (element) {
    const rows = JSON.parse(Laravel.transcriptions);
    const chart = initChart(element);

    chart.setOption({
        color: palette,
        tooltip: {
            trigger: 'axis',
            axisPointer: {type: 'shadow'},
            formatter: ([point]) => `${numberFormat.format(point.value)} participants: ${point.name} digitizations`,
        },
        grid: {left: 70, right: 20, top: 20, bottom: 60},
        xAxis: {
            type: 'category',
            name: 'Digitizations',
            nameLocation: 'middle',
            nameGap: 36,
            data: rows.map((row) => String(row.transcriptions)),
        },
        yAxis: {
            type: 'value',
            name: 'Number of Participants',
            nameLocation: 'middle',
            nameGap: 50,
            axisLabel: {formatter: (value) => numberFormat.format(value)},
        },
        series: [{
            type: 'bar',
            data: rows.map((row) => Number(row.transcribers)),
        }],
    });

    renderDataTable(document.getElementById('statDiv-table'), 'Digitizations and number of participants',
        ['Digitizations', 'Number of participants'],
        rows.map((row) => [String(row.transcriptions), Number(row.transcribers)]));
}
