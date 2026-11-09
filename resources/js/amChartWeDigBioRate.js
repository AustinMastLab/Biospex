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

import {loadAmCharts} from './amcharts-loader.js';

let weDigBioRateChart = null;
let interval = null;
let modalOpen = false;

// The modal is in the menu of every page, so amCharts is only loaded the first time it opens.
$('#wedigbio-rate-modal').on('modal:show', function (e) {
    modalOpen = true;
    let $trigger = $(e.originalEvent.detail.relatedTarget);
    let $div = $(this).find('#weDigBioRateChartDiv');
    let projectsUrl = $trigger.data('projects');
    let url = $trigger.data('href');

    let createChart = function () {
        am4core.disposeAllCharts();
        $.get(projectsUrl).fail(function () {
            $div.html('<p class="d-flex justify-content-center">Failed to load projects</p>');
        }).done(function (projects) {
            if (!projects) {
                $div.html('<p class="d-flex justify-content-center">No current WeDigBio Event</p>');
                return;
            }
            buildChart(url, projects)
        });
    }

    loadAmCharts().then(function () {
        // Closed while amCharts was loading
        if (!modalOpen) {
            return;
        }

        createChart();
        // Refresh chart every 5 minutes. TODO: can this be handled by amCharts?
        // https://www.amcharts.com/docs/v4/concepts/data/loading-external-data/
        // chart.dataSource.reloadFrequency = 300000;
        interval = setInterval(createChart, 300000);
    }).catch(function () {
        $div.html('<p class="d-flex justify-content-center">Failed to load the chart</p>');
    });
}).on('modal:hidden', function () {
    modalOpen = false;
    window.am4core?.disposeAllCharts();
    clearInterval(interval);
});


function buildChart(url, projects) {
    weDigBioRateChart = am4core.create("weDigBioRateChartDiv", am4charts.XYChart);
    am4core.useTheme(am4themes_animated);

    // A11y: label only the root SVG (safe; avoids DOM churn)
    function labelRootSvgOnly() {
        try {
            let container = document.getElementById('weDigBioRateChartDiv');
            if (!container) return;

            let svg = container.querySelector('svg');
            if (!svg) return;

            let titleId = 'wedigbio-rate-chart-svg-title';
            let descId = 'wedigbio-rate-chart-svg-desc';

            let title = svg.querySelector('#' + titleId);
            if (!title) {
                title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                title.setAttribute('id', titleId);
                svg.insertBefore(title, svg.firstChild);
            }
            title.textContent = 'WeDigBio rate chart';

            let desc = svg.querySelector('#' + descId);
            if (!desc) {
                desc = document.createElementNS('http://www.w3.org/2000/svg', 'desc');
                desc.setAttribute('id', descId);
                svg.insertBefore(desc, title.nextSibling);
            }
            desc.textContent = 'Line chart showing estimated records per hour over time by project.';

            svg.setAttribute('role', 'img');
            svg.setAttribute('aria-labelledby', titleId + ' ' + descId);
        } catch (e) {
            // Intentionally swallow to avoid breaking the chart
        }
    }

    weDigBioRateChart.events.on("ready", labelRootSvgOnly);
    weDigBioRateChart.events.on("datavalidated", labelRootSvgOnly);

    weDigBioRateChart.dataSource.url = url;
    weDigBioRateChart.dateFormatter.inputDateFormat = "yyyy-MM-dd HH:mm:ss";
    weDigBioRateChart.hiddenState.properties.opacity = 0;
    weDigBioRateChart.padding(0, 0, 0, 0);
    // Shown once the chart is zoomed in (see below), to go back to the whole event.
    weDigBioRateChart.zoomOutButton.disabled = false;

    // Once, when the chart first appears, so it doesn't undo the user's own zooming.
    weDigBioRateChart.events.once("ready", function () {
        zoomToStartingView(weDigBioRateChart, dateAxis);
    });

    weDigBioRateChart.scrollbarX = null;

    let dateAxis = weDigBioRateChart.xAxes.push(new am4charts.DateAxis());

    // The data has a point every 5 minutes. On a long range, amCharts groups them into larger periods
    // (averaged per series, below), so the chart stays readable and fast instead of drawing every point.
    dateAxis.baseInterval = {timeUnit: "minute", count: 5};
    dateAxis.groupData = true;
    dateAxis.groupCount = 300;

    dateAxis.renderer.minGridDistance = 20;
    dateAxis.title.text = 'UTC Timezone';
    dateAxis.title.fontSize = 20;
    dateAxis.title.align = 'center';
    dateAxis.title.fontWeight = 600;
    dateAxis.dateFormats.setKey("minute", "hh:mm");
    dateAxis.periodChangeDateFormats.setKey("minute", "[bold]h:mm a");
    dateAxis.periodChangeDateFormats.setKey("hour", "[bold]h:mm a");
    dateAxis.renderer.axisFills.template.disabled = true;
    dateAxis.renderer.ticks.template.disabled = true;

    // this makes date axis labels which are at equal minutes to be rotated
    dateAxis.renderer.labels.template.adapter.add("rotation", function (rotation, target) {
        target.verticalCenter = "middle";
        target.horizontalCenter = "left";
        return -90;
    });

    // Enable scroll-like navigation on time without a scrollbar:
    // - drag to pan horizontally
    // - mouse wheel / trackpad to zoom on X (time)
    let cursor = new am4charts.XYCursor();
    cursor.behavior = "panX";
    cursor.lineY.disabled = true;
    cursor.lineX.disabled = true; // optional: hide the crosshair line for a cleaner look
    weDigBioRateChart.cursor = cursor;

    // Trackpad/mouse wheel zoom on time axis
    weDigBioRateChart.mouseWheelBehavior = "zoomX";

    // Optional: allow touch panning on mobile
    weDigBioRateChart.chartContainer.wheelable = true;

    let valueAxis = weDigBioRateChart.yAxes.push(new am4charts.ValueAxis());
    //valueAxis.min = -0.1;
    valueAxis.title.text = "Estimated Records Per Hour";
    valueAxis.title.fontSize = 20;
    valueAxis.tooltip.disabled = true;
    valueAxis.renderer.minLabelPosition = 0.05;
    valueAxis.renderer.maxLabelPosition = 0.95;
    valueAxis.renderer.axisFills.template.disabled = true;
    valueAxis.renderer.ticks.template.disabled = true;

    $.each(projects, function (index, value) {
        let project = weDigBioRateChart.series.push(new am4charts.LineSeries());
        project.dataFields.dateX = "date";
        project.dataFields.valueY = value;
        project.name = value;
        project.strokeWidth = 2;
        project.dataItems.template.locations.dateX = 0;
        project.interpolationDuration = 500;
        project.defaultState.transitionDuration = 0;
        project.tensionX = 0.8;
        // Values are rates (records per hour), so a grouped period shows their average, not their sum.
        project.groupFields.valueY = "average";

        let bullet = project.createChild(am4charts.CircleBullet);
        bullet.circle.radius = 5;
        bullet.fillOpacity = 1;
        bullet.fill = weDigBioRateChart.colors.getIndex(1);
        bullet.isMeasured = false;

        project.events.on("validated", function () {
            if (project.dataItems.last == null) return;

            bullet.moveTo(project.dataItems.last.point);
            bullet.validatePosition();
        });
    });

    // The legend lives in its own container under the chart, so each project adds a legend row there instead of
    // squeezing the plot, which keeps the height set on #weDigBioRateChartDiv.
    let legendContainer = am4core.create("weDigBioRateLegendDiv", am4core.Container);
    legendContainer.width = am4core.percent(100);
    legendContainer.height = am4core.percent(100);

    weDigBioRateChart.legend = new am4charts.Legend();
    weDigBioRateChart.legend.parent = legendContainer;
    weDigBioRateChart.legend.events.on("datavalidated", resizeLegend);
    weDigBioRateChart.events.on("maxsizechanged", resizeLegend);

    function resizeLegend() {
        document.getElementById("weDigBioRateLegendDiv").style.height = weDigBioRateChart.legend.contentHeight + "px";
    }
    weDigBioRateChart.legend.labels.template.text = "[bold]{name}";
    weDigBioRateChart.legend.useDefaultMarker = true;
    let marker = weDigBioRateChart.legend.markers.template.children.getIndex(0);
    marker.cornerRadius(12, 12, 12, 12);
    marker.strokeWidth = 2;
    marker.strokeOpacity = 1;
}

/**
 * Starting view: the last 24 hours while the event is running (its data reaches the present), otherwise the whole
 * event. The zoom-out button returns to the whole event.
 *
 * Decided from the data rows (whose dates amCharts has parsed by now) rather than the axis, whose end moves out to the
 * edge of a grouped period.
 */
function zoomToStartingView(chart, dateAxis) {
    const rows = chart.data;
    if (!rows || rows.length === 0) {
        return;
    }

    const toTime = (value) => (value instanceof Date ? value : chart.dateFormatter.parse(value, "yyyy-MM-dd HH:mm:ss")).getTime();
    const first = toTime(rows[0].date);
    const last = toTime(rows[rows.length - 1].date);
    const day = 24 * 60 * 60 * 1000;
    // The chart shows UTC times as they are (the axis is titled "UTC Timezone"), so compare with the current UTC
    // time read the same way.
    const nowOnAxis = Date.now() + new Date().getTimezoneOffset() * 60 * 1000;
    const isRunning = Math.abs(nowOnAxis - last) < 30 * 60 * 1000;

    if (isRunning && last - first > day) {
        dateAxis.zoomToDates(new Date(last - day), new Date(last), false, true);
    }
}
