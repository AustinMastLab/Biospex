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

let chart;
am4core.ready(function () {

    $('#step-chart-modal').on('show.bs.modal', function (e) {

        let teams = $(e.relatedTarget).data('teams').split(',');
        let timezone = $(e.relatedTarget).data('timezone');
        let url = $(e.relatedTarget).data('href');
        let refresh = initialRefresh();

        am4core.useTheme(am4themes_animated);

        chart = am4core.create("chartdiv", am4charts.XYChart);

        // Keep this VERY lightweight: label only the root SVG once per render cycle.
        function labelRootSvgOnly() {
            try {
                let container = document.getElementById('chartdiv');
                if (!container) return;

                let svg = container.querySelector('svg');
                if (!svg) return;

                let titleId = 'event-rate-chart-svg-title';
                let descId = 'event-rate-chart-svg-desc';

                let title = svg.querySelector('#' + titleId);
                if (!title) {
                    title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                    title.setAttribute('id', titleId);
                    svg.insertBefore(title, svg.firstChild);
                }
                title.textContent = 'Rate chart';

                let desc = svg.querySelector('#' + descId);
                if (!desc) {
                    desc = document.createElementNS('http://www.w3.org/2000/svg', 'desc');
                    desc.setAttribute('id', descId);
                    svg.insertBefore(desc, title.nextSibling);
                }
                desc.textContent = 'Line chart showing estimated records per hour over time by team.';

                svg.setAttribute('role', 'img');
                svg.setAttribute('aria-labelledby', titleId + ' ' + descId);
            } catch (e) {
                // Never let accessibility patching break the chart
                // (intentionally swallow)
            }
        }

        chart.events.on("ready", labelRootSvgOnly);
        chart.events.on("datavalidated", labelRootSvgOnly);

        chart.dataSource.url = url;
        chart.dataSource.reloadFrequency = refresh;
        chart.dataSource.incremental = true;
        chart.dataSource.adapter.add("url", function (url, target) {
            if (target.lastLoad) {
                chart.dataSource.reloadFrequency = 300000;
                url += "/" + target.lastLoad.getTime();
            }
            return url;
        });
        chart.dateFormatter.inputDateFormat = "yyyy-MM-dd HH:mm:ss";
        chart.hiddenState.properties.opacity = 0;
        chart.padding(0, 0, 0, 0);
        chart.zoomOutButton.disabled = true;

        let dateAxis = chart.xAxes.push(new am4charts.DateAxis());
        dateAxis.baseInterval = { "timeUnit": "minute", "count": 5 };

        chart.events.on("datavalidated", function () {
            dateAxis.zoom({start: 1 / 15, end: 1.2}, false, true);
        });

        chart.scrollbarX = null;

        let cursor = new am4charts.XYCursor();
        cursor.behavior = "panX";
        cursor.lineY.disabled = true;
        cursor.lineX.disabled = true;
        chart.cursor = cursor;

        chart.mouseWheelBehavior = "zoomX";
        chart.chartContainer.wheelable = true;

        dateAxis.renderer.minGridDistance = 20;
        dateAxis.title.text = timezone;
        dateAxis.title.fontSize = 20;
        dateAxis.dateFormats.setKey("minute", "hh:mm");
        dateAxis.periodChangeDateFormats.setKey("minute", "[bold]h:mm a");
        dateAxis.periodChangeDateFormats.setKey("hour", "[bold]h:mm a");
        dateAxis.renderer.axisFills.template.disabled = true;
        dateAxis.renderer.ticks.template.disabled = true;
        dateAxis.interpolationDuration = 500;
        dateAxis.rangeChangeDuration = 500;

        dateAxis.renderer.labels.template.adapter.add("rotation", function (rotation, target) {
            target.verticalCenter = "middle";
            target.horizontalCenter = "left";
            return -90;
        });

        let valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
        valueAxis.title.text = "Estimated Records Per Hour";
        valueAxis.title.fontSize = 20;
        valueAxis.tooltip.disabled = true;
        valueAxis.interpolationDuration = 500;
        valueAxis.rangeChangeDuration = 500;
        valueAxis.renderer.minLabelPosition = 0.05;
        valueAxis.renderer.maxLabelPosition = 0.95;
        valueAxis.renderer.axisFills.template.disabled = true;
        valueAxis.renderer.ticks.template.disabled = true;

        $.each(teams, function (index, value) {
            let team = chart.series.push(new am4charts.LineSeries());
            team.dataFields.dateX = "date";
            team.dataFields.valueY = value;
            team.name = value;
            team.strokeWidth = 2;
            team.dataItems.template.locations.dateX = 0;
            team.interpolationDuration = 500;
            team.defaultState.transitionDuration = 0;
            team.tensionX = 0.8;

            let bullet = team.createChild(am4charts.CircleBullet);
            bullet.circle.radius = 5;
            bullet.fillOpacity = 1;
            bullet.fill = chart.colors.getIndex(1);
            bullet.isMeasured = false;

            team.events.on("validated", function () {
                if (team.dataItems.last == null) return;
                bullet.moveTo(team.dataItems.last.point);
                bullet.validatePosition();
            });
        });

        chart.legend = new am4charts.Legend();
        chart.legend.labels.template.text = "[bold]{name}";
        chart.legend.useDefaultMarker = true;
        let marker = chart.legend.markers.template.children.getIndex(0);
        marker.cornerRadius(12, 12, 12, 12);
        marker.strokeWidth = 2;
        marker.strokeOpacity = 1;
        marker.stroke = am4core.color("#ccc");

        function initialRefresh() {
            let coeff = 1000 * 60 * 5;
            let date = new Date();
            let rounded = new Date(Math.ceil(date.getTime() / coeff) * coeff);
            return (rounded.getTime() - date.getTime());
        }

        $('#step-chart-modal').one('hidden.bs.modal', function () {
            stopSvgA11yObserver();
        });

    }).on('hidden.bs.modal', function () {
        chart.dispose();
    });

}); // end am4core.ready()