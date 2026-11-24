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
 * The project page's Digitized Specimens Heat Map: digitized specimens per state; a state's button shows its counties.
 *
 * Outlines come from us-atlas (US Census, laid out with Alaska and Hawaii as insets), a TopoJSON file served with the
 * site and fetched when the map is drawn. Its coordinates are already projected, so ECharts draws them as they are.
 * The 50 states and DC are shown; the territories don't fit the inset layout.
 */
import {feature} from 'topojson-client';
import usAtlasUrl from 'us-atlas/counties-albers-10m.json?url';
import {echarts, initChart, numberFormat, renderDataTable} from './echarts.js';

const element = document.getElementById('state-map');

const asIs = {project: (point) => point, unproject: (point) => point};
// Both ends at least 4.5:1 against white (the old light gray was 2.3:1); the data table gives every value.
const heatColors = ['#767676', '#aa0002'];

/**
 * A round legend maximum: the largest value rounded up to the next thousand.
 */
function legendMax(values) {
    return Math.max(1000, Math.ceil(Math.max(0, ...values) / 1000) * 1000);
}

if (element) {
    const chart = initChart(element);
    const states = JSON.parse(element.dataset.states);
    const title = document.getElementById('state-map-title');
    const back = document.getElementById('state-map-back');
    const table = document.getElementById('state-map-table');
    let atlas = null;

    async function loadAtlas() {
        atlas ??= await fetch(usAtlasUrl).then((response) => response.json());

        return atlas;
    }

    function draw(mapName, data) {
        const max = legendMax(data.map((area) => area.value));

        chart.setOption({
            tooltip: {
                trigger: 'item',
                formatter: (params) => `${params.data?.label ?? params.name}: ${numberFormat.format(params.value || 0)}`,
            },
            visualMap: {
                type: 'continuous',
                min: 0,
                max,
                inRange: {color: heatColors},
                orient: 'horizontal',
                left: 'center',
                bottom: 0,
                itemWidth: 14,
                itemHeight: 260,
                text: [numberFormat.format(max), '0'],
                calculable: false,
                realtime: false,
            },
            series: [{
                type: 'map',
                map: mapName,
                projection: asIs,
                top: 10,
                bottom: 60,
                selectedMode: false,
                itemStyle: {areaColor: heatColors[0], borderColor: '#ffffff', borderWidth: 0.6},
                emphasis: {label: {show: false}, itemStyle: {areaColor: '#8F3985'}},
                data,
            }],
        }, {notMerge: true});
    }

    async function showStates() {
        chart.showLoading();
        const atlas = await loadAtlas();
        chart.hideLoading();

        if (!echarts.getMap('us-states')) {
            const outlines = feature(atlas, atlas.objects.states);
            outlines.features = outlines.features.map((state) => ({...state, properties: {name: state.id}}));
            echarts.registerMap('us-states', outlines);
        }

        const names = Object.fromEntries(atlas.objects.states.geometries.map((state) => [state.id, state.properties.name]));
        const data = states
            .map((state) => ({...state, fips: String(state.statenum).padStart(2, '0')}))
            .filter((state) => names[state.fips] !== undefined)
            .map((state) => ({name: state.fips, value: state.value, label: names[state.fips], statenum: state.statenum}))
            .sort((a, b) => a.label.localeCompare(b.label));

        draw('us-states', data);
        title.textContent = 'United States';
        back.hidden = true;

        renderDataTable(table, 'Digitized specimens by state', ['State', 'Digitized specimens'],
            data.map((state) => [state.label, state.value]));
        addCountyButtons(data);
    }

    /**
     * Each state's row gets a button that shows its counties, so they can be reached without the map.
     */
    function addCountyButtons(data) {
        table.querySelectorAll('tbody tr').forEach((row, index) => {
            const state = data[index];
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-link p-0 text-left';
            button.textContent = state.label;
            button.addEventListener('click', () => showCounties(state));
            row.cells[0].replaceChildren(button);
        });
    }

    async function showCounties(state) {
        chart.showLoading();

        try {
            const atlas = await loadAtlas();
            const mapName = `us-state-${state.name}`;

            if (!echarts.getMap(mapName)) {
                const outlines = feature(atlas, atlas.objects.counties);
                outlines.features = outlines.features.filter((county) => county.id.startsWith(state.name))
                    .map((county) => ({...county, properties: {name: county.id}}));
                echarts.registerMap(mapName, outlines);
            }

            const response = await fetch(`${element.dataset.countyUrl}/${state.statenum}`, {
                headers: {'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json'},
            });
            const result = await response.json();
            const counties = JSON.parse(result.counties)
                .map((county) => ({name: county.id, value: county.value, label: county.name.replace(/^[A-Z]{2}-/, '')}))
                .sort((a, b) => a.label.localeCompare(b.label));

            draw(mapName, counties);
            title.textContent = state.label;
            back.hidden = false;
            back.focus();

            renderDataTable(table, `Digitized specimens by county in ${state.label}`, ['County', 'Digitized specimens'],
                counties.map((county) => [county.label, county.value]));
        } finally {
            chart.hideLoading();
        }
    }

    chart.on('click', (params) => {
        if (params.seriesType === 'map' && params.data?.statenum !== undefined) {
            showCounties(params.data);
        }
    });
    back.addEventListener('click', showStates);

    showStates();
}
