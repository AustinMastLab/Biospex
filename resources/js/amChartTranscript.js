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

$(function () {
    am4core.options.autoDispose = true;

    let a11yScheduled = false;
    let transcriptChartA11yObserver = null;

    function scheduleTranscriptChartA11yFix() {
        if (a11yScheduled) return;
        a11yScheduled = true;

        window.requestAnimationFrame(function () {
            a11yScheduled = false;
            fixTranscriptChartSvgA11y();
        });
    }

    function makeNodeUnfocusable(node) {
        node.removeAttribute('tabindex');
        node.removeAttribute('focusable');
        node.removeAttribute('role');
        node.removeAttribute('aria-label');
        node.removeAttribute('aria-labelledby');
        node.removeAttribute('aria-controls');
        node.removeAttribute('aria-checked');
        node.removeAttribute('aria-hidden');
        node.setAttribute('tabindex', '-1');
        node.setAttribute('focusable', 'false');
        node.setAttribute('aria-hidden', 'true');
    }

    function fixTranscriptChartSvgA11y() {
        try {
            const container = document.getElementById('transcripts');
            if (!container) return;

            const svg = container.querySelector('svg');
            if (!svg) return;

            svg.setAttribute('aria-hidden', 'true');
            svg.setAttribute('focusable', 'false');

            svg.querySelectorAll(
                '[role], [aria-label], [aria-labelledby], [aria-controls], [aria-checked]'
            ).forEach(function (node) {
                node.removeAttribute('role');
                node.removeAttribute('aria-label');
                node.removeAttribute('aria-labelledby');
                node.removeAttribute('aria-controls');
                node.removeAttribute('aria-checked');
            });

            svg.querySelectorAll(
                '[tabindex], [focusable], [role="switch"], [aria-controls], [aria-checked]'
            ).forEach(function (node) {
                makeNodeUnfocusable(node);
            });

            svg.querySelectorAll(
                '[aria-hidden="true"], [display="none"], [visibility="hidden"], [opacity="0"]'
            ).forEach(function (node) {
                makeNodeUnfocusable(node);

                node.querySelectorAll(
                    'a, button, [tabindex], [focusable], [role], [aria-controls], [aria-checked]'
                ).forEach(function (child) {
                    makeNodeUnfocusable(child);
                });
            });
        } catch (e) {
            // swallow
        }
    }

    function observeTranscriptChartA11y() {
        const container = document.getElementById('transcripts');
        if (!container) return;

        if (transcriptChartA11yObserver) {
            transcriptChartA11yObserver.disconnect();
        }

        transcriptChartA11yObserver = new MutationObserver(function (mutations) {
            for (const mutation of mutations) {
                if (mutation.type === 'childList' || mutation.type === 'attributes') {
                    scheduleTranscriptChartA11yFix();
                    return;
                }
            }
        });

        transcriptChartA11yObserver.observe(container, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: [
                'tabindex',
                'focusable',
                'role',
                'aria-hidden',
                'aria-label',
                'aria-labelledby',
                'display',
                'visibility',
                'opacity',
            ],
        });
    }

    let buildChart = function (data) {
                let transcripts = am4core.createFromConfig(data, "transcripts", am4charts.XYChart);

                transcripts.zoomOutButton.disabled = true;
                transcripts.mouseWheelBehavior = "zoomX";
                transcripts.chartContainer.wheelable = true;

                if (!transcripts.cursor) {
                    transcripts.cursor = new am4charts.XYCursor();
                }
                transcripts.cursor.behavior = "panX";
                transcripts.cursor.lineY.disabled = true;

                let cellSize = 1.5;
                transcripts.events.on("datavalidated", function (ev) {
                    let chart = ev.target;
                    let categoryAxis = chart.yAxes.getIndex(0);

                    let adjustHeight = chart.data.length * cellSize - categoryAxis.pixelHeight;
                    let targetHeight = chart.pixelHeight + adjustHeight;

                    chart.svgContainer.htmlElement.style.height = targetHeight + "px";

                    scheduleTranscriptChartA11yFix();
                });

                transcripts.events.on('ready', function () {
                    $("#script-modal").modal("hide");
                    observeTranscriptChartA11y();
                    scheduleTranscriptChartA11yFix();
                });
            },
        loadChart = function (url) {
            let ds = new am4core.DataSource();
            ds.url = url;
            ds.disableCache = true;
            ds.events.on("done", function (ev) {
                buildChart(ev.target.data);
            });
            ds.load();
        };

    let years = Laravel.years;
    if (years.length > 0) {
        let el = $('#year' + years[0]);
        let url = el.data('href');
        el.removeClass('btn-primary').addClass('btn-transcription-year');
        loadChart(url);
    }

    $('.btn-transcription').on('click', function () {
        $("#script-modal").modal("show");
        $(this).removeClass('btn-primary').addClass('btn-transcription-year');
        $(this).siblings().removeClass('btn-transcription-year').addClass('btn-primary');
        let url = $(this).data('href');
        loadChart(url);
    });
});