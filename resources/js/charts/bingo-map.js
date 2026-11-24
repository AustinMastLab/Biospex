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
 * The bingo card's map of where players are. The map is hidden from screen readers; a list of the players' places
 * goes with it (WCAG 1.1.1) and grows as players join.
 *
 * Outlines come from world-atlas (Natural Earth), a TopoJSON file served with the site. They are projected (Miller)
 * with d3-geo-projection, which also cuts countries that cross the 180th meridian; ECharts draws the result as it is.
 */
import {feature} from 'topojson-client';
import {geoMiller, geoProject} from 'd3-geo-projection';
import worldAtlasUrl from 'world-atlas/countries-110m.json?url';
import {echarts, initChart} from './echarts.js';

const asIs = {project: (point) => point, unproject: (point) => point};
const antarctica = '010';

/**
 * Draw the map with the players' markers.
 *
 * @param {HTMLElement} element
 * @param {Array<{city: string, latitude: number|string, longitude: number|string}>} players
 * @param {HTMLElement|null} list The list of places (a <ul>)
 * @param {HTMLElement|null} count Where the number of players goes
 * @returns {Promise<{addPlayer: function(object): void}>}
 */
export async function createBingoMap(element, players, {list = null, count = null} = {}) {
    const listPlayer = (player) => {
        if (list) {
            const item = document.createElement('li');
            item.textContent = player.city;
            list.appendChild(item);
        }
        if (count) {
            count.textContent = String(list ? list.children.length : 0);
        }
    };
    players.forEach(listPlayer);

    const chart = initChart(element);
    const miller = geoMiller();

    if (!echarts.getMap('world')) {
        const atlas = await fetch(worldAtlasUrl).then((response) => response.json());
        const countries = feature(atlas, atlas.objects.countries);
        countries.features = countries.features.filter((country) => country.id !== antarctica);
        echarts.registerMap('world', geoProject(countries, miller));
    }

    const markers = [];
    const marker = (player) => ({
        name: player.city,
        value: miller([Number(player.longitude), Number(player.latitude)]),
    });
    players.forEach((player) => markers.push(marker(player)));

    chart.setOption({
        tooltip: {trigger: 'item', formatter: (params) => params.name},
        geo: {
            map: 'world',
            projection: asIs,
            roam: false,
            top: 10,
            bottom: 10,
            // 3.9:1 against white (the old green was 2.5:1).
            itemStyle: {areaColor: '#4f8f42', borderColor: '#ffffff', borderWidth: 0.5},
            emphasis: {label: {show: false}, itemStyle: {areaColor: '#2f6526'}},
            select: {disabled: true},
            tooltip: {show: true, formatter: (params) => params.name},
        },
        series: [{
            type: 'scatter',
            coordinateSystem: 'geo',
            symbol: 'pin',
            symbolSize: 26,
            symbolOffset: [0, '-50%'],
            // A white edge keeps the pins clear against the green.
            itemStyle: {color: '#aa0002', borderColor: '#ffffff', borderWidth: 1.5},
            data: markers,
        }],
    });

    return {
        addPlayer(player) {
            listPlayer(player);
            markers.push(marker(player));
            chart.setOption({series: [{data: markers}]});
        },
    };
}
