/*
 * Copyright (C) 2014 - 2025, Biospex
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

function renderA11yTableFromTranscriptions(containerId, data) {
    const host = document.getElementById(containerId);
    if (!host) return;

    if (!Array.isArray(data) || data.length === 0) {
        host.innerHTML = "";
        return;
    }

    const rowsHtml = data.map((row) => {
        const digitizations = row?.transcriptions ?? "";
        const participants = row?.transcribers ?? "";
        return `<tr><td>${String(digitizations)}</td><td>${String(participants)}</td></tr>`;
    }).join("");

    host.innerHTML = `
        <table>
            <caption>Digitizations and number of participants</caption>
            <thead>
                <tr>
                    <th scope="col">Digitizations</th>
                    <th scope="col">Number of participants</th>
                </tr>
            </thead>
            <tbody>
                ${rowsHtml}
            </tbody>
        </table>
    `;
}

let stats = am4core.createFromConfig(
    {
        "xAxes": [{
            "type": "CategoryAxis",
            "title": {
                "text": "Digitizations"
            },
            "dataFields": {
                "category": "transcriptions"
            },
            "tooltip": {
                "background": {
                    "fill": "#07BEB8",
                    "strokeWidth": 0,
                    "cornerRadius": 3,
                    "pointerLength": 0
                },
                "dy": 5
            }
        }],
        "yAxes": [{
            "type": "ValueAxis",
            "title": {
                "text": "Number of Participants"
            },
            "tooltip": {
                "disabled": true
            },
            "calculateTotals": true
        }],

        // Enable mouse wheel / trackpad zoom on X (horizontal axis)
        "mouseWheelBehavior": "zoomX",

        // Ensure wheel events are captured by the chart container
        "chartContainer": {
            "wheelable": true
        },

        // Drag to pan horizontally (instead of needing a scrollbar)
        "cursor": {
            "type": "XYCursor",
            "behavior": "panX",
            "lineX": {
                "stroke": "#8F3985",
                "strokeWidth": 4,
                "strokeOpacity": 0.2,
                "strokeDasharray": ""
            },
            "lineY": {
                "disabled": true
            }
        },

        // Keep scrollbar removed to avoid Axe nested-interactive
        "scrollbarX": null,

        "series": [{
            "type": "ColumnSeries",
            "dataFields": {
                "valueY": "transcribers",
                "categoryX": "transcriptions"
            },
            "tooltipHTML": "<span style='color:#000000;'>{valueY.value} Participants: {categoryX} Digitizations</span>",
            "tooltip": {
                "background": {
                    "fill": "#FFF",
                    "strokeWidth": 1
                },
                "getStrokeFromObject": true,
                "getFillFromObject": false
            },
            "fillOpacity": 0.8,
            "strokeWidth": 0,
            "stacked": true
        }],
        "data": JSON.parse(Laravel.transcriptions),
    }, "statDiv", am4charts.XYChart
);

// Use the same data the chart uses
const statData = JSON.parse(Laravel.transcriptions);
renderA11yTableFromTranscriptions("statDiv-a11y-table", statData);

function hardenChartSvg(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const svg = container.querySelector("svg");
    if (!svg) return;

    // Keep screen readers out of amCharts' SVG internals (use HTML content for accessibility instead)
    svg.setAttribute("aria-hidden", "true");
    svg.setAttribute("focusable", "false");

    // Remove invalid role patterns that trigger aria-required-parent
    svg.querySelectorAll('[role="listitem"]').forEach((el) => el.removeAttribute("role"));
}

// Run after render + after updates/resizes (amCharts v4 re-renders frequently)
if (stats && stats.events) {
    const run = () => hardenChartSvg("statDiv");

    stats.events.on("ready", run);
    stats.events.on("validated", run);
    stats.events.on("datavalidated", run);
    stats.events.on("sizechanged", run);
}