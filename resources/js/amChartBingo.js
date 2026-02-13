/*
 * Copyright (C) 2014 - 2025, Biospex
 * biospex@gmail.com
 *
 * // ... existing header ...
 */

$(function () {

    // Create map instance
    let chart = am4core.create("bingodiv", am4maps.MapChart);

    // Keep the map out of the tab order (amCharts may inject focusable elements)
    makeMapNotTabbable(chart);

    // Set map definition
    chart.geodata = am4geodata_worldLow;

    // Set projection
    chart.projection = new am4maps.projections.Miller();

    // Create map polygon series
    let polygonSeries = chart.series.push(new am4maps.MapPolygonSeries());

    // Make map load polygon (like country names) data from GeoJSON
    polygonSeries.useGeodata = true;

    // Configure series
    let polygonTemplate = polygonSeries.mapPolygons.template;
    polygonTemplate.tooltipText = "{name}";
    polygonTemplate.fill = am4core.color("#74B266");

    // Create hover state and set alternative fill color
    let hs = polygonTemplate.states.create("hover");
    hs.properties.fill = am4core.color("#367B25");

    // Remove Antarctica
    polygonSeries.exclude = ["AQ"];

    // Create image series
    let imageSeries = chart.series.push(new am4maps.MapImageSeries());

    let imageSeriesTemplate = imageSeries.mapImages.template;
    let marker = imageSeriesTemplate.createChild(am4core.Image);
    marker.href = "https://s3-us-west-2.amazonaws.com/s.cdpn.io/t-160/marker.svg";
    marker.width = 20;
    marker.height = 20;
    marker.nonScaling = true;
    marker.tooltipText = "{city}";
    marker.horizontalCenter = "middle";
    marker.verticalCenter = "bottom";

    // Set property fields
    imageSeriesTemplate.propertyFields.latitude = "latitude";
    imageSeriesTemplate.propertyFields.longitude = "longitude";
    imageSeries.data = JSON.parse(Laravel.bingoUserData);

    Echo.channel(Laravel.channel)
        .listen('BingoEvent', (e) => {
            let data = JSON.parse(e.data);
            let winner = data.winner;
            let marker = data.marker;

            if (marker['uuid'] === Laravel.bingoUserUuid) {
                return;
            }

            // imageSeries.addData(item.value);
            imageSeries.addData(data.marker);

            if (winner === null) {
                return;
            }

            showWinnerModal($bingoRows, '<h3>We Have A Winner In ' + winner.city + '!!</h3>');
        });

    let $bingoRows = $('#bingo-rows');
    createRows($bingoRows);

    // Set winning combinations to array
    let winners = [['a1', 'a2', 'a3', 'a4', 'a5'], ['b1', 'b2', 'b3', 'b4', 'b5'], ['c1', 'c2', 'c3', 'c4', 'c5'], ['d1', 'd2', 'd3', 'd4', 'd5'], ['e1', 'e2', 'e3', 'e4', 'e5'], ['a1', 'b1', 'c1', 'd1', 'e1'], ['a2', 'b2', 'c2', 'd2', 'e2'], ['a3', 'b3', 'c3', 'd3', 'e3'], ['a4', 'b4', 'c4', 'd4', 'e4'], ['a5', 'b5', 'c5', 'd5', 'e5'], ['a1', 'b2', 'c3', 'd4', 'e5'], ['a5', 'b4', 'c3', 'd2', 'e1']];
    let possibleWinners = winners.length;

    // Initialize selected array with c3 freebie
    let selected = ['c3'];

    // Toggle clicked and not clicked
    $bingoRows.on('click', '.square', function () {
        const $cell = $(this);

        // Skip the logo square
        if ($cell.hasClass('logo')) {
            return;
        }

        $cell.toggleClass('clicked');

        // Keep aria-pressed in sync for screen readers
        $cell.attr('aria-pressed', $cell.hasClass('clicked') ? 'true' : 'false');

        // Push clicked object ID to 'selected' array
        selected.push($cell.attr('id'));

        // Compare winners array to selected array for matches
        for (let i = 0; i < possibleWinners; i++) {
            let cellExists = 0;

            for (let j = 0; j < 5; j++) {
                if ($.inArray(winners[i][j], selected) > -1) {
                    cellExists++;
                }
            }

            // If all 5 winner cells exist in selected array alert success message
            if (cellExists === 5) {
                $.get(Laravel.winnerUrl);
                showWinnerModal($bingoRows, '<h3>You are a winner!! Congratulations!!</h3>', true);
                selected = ['c3'];
            }
        }
    });

    // Keyboard support: Enter / Space toggles the focused cell
    $bingoRows.on('keydown', '.square', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') {
            return;
        }

        e.preventDefault();
        $(this).trigger('click');
    });

    // ... existing code ...
});

function showWinnerModal($bingoRows, msg, owner = false) {
    $('#bingo-modal').modal('show').on('shown.bs.modal', function () {
        $('#bingo-conffeti').collapse('show');
        $body = $(this).find('.modal-body');
        $body.html(msg);

        timeout = setTimeout(function () {
            $('#bingo-modal').modal('hide')
            $('#bingo-conffeti').collapse('hide');
            $body.html('');
        }, 10000);
    }).on('hidden.bs.modal', function () {
        createRows($bingoRows);
        $('#bingo-conffeti').collapse('hide');
    });
}

function createRows($bingoRows) {
    $bingoRows.html('');
    $.post(Laravel.rowsUrl)
        .done(function (data) {
            $bingoRows.html(data);

            // After rows are injected, set up focusability in DOM order
            initBingoCellTabOrder($bingoRows);
        })
        .fail(function () {
            $bingoRows.html('<p>Failed to load bingo rows.</p>');
        });
}

function initBingoCellTabOrder($bingoRows) {
    // Make ONLY the playable cells tabbable, in DOM creation order.
    // (DOM order == the order they appear in the injected HTML.)
    const $cells = $bingoRows.find('.square').not('.logo');

    $cells.each(function () {
        const $cell = $(this);

        // Make div behave like a button for a11y
        $cell.attr('tabindex', '0');
        $cell.attr('role', 'button');

        // Keep aria-pressed aligned with state
        $cell.attr('aria-pressed', $cell.hasClass('clicked') ? 'true' : 'false');
    });

    // Ensure the logo is not tabbable
    $bingoRows.find('.square.logo').removeAttr('tabindex').removeAttr('role').removeAttr('aria-pressed');
}

function makeMapNotTabbable(chart) {
    const mapEl = document.getElementById('bingodiv');
    if (!mapEl) {
        return;
    }

    // Container itself should not be in tab order
    mapEl.setAttribute('tabindex', '-1');

    // amCharts chart container element can be focusable; remove it from tab order
    if (chart && chart.chartContainer && chart.chartContainer.htmlElement) {
        chart.chartContainer.htmlElement.setAttribute('tabindex', '-1');
    }

    // amCharts may insert SVG elements with tabindex="0"; neutralize them as they appear
    const stripTabindex = () => {
        mapEl.querySelectorAll('[tabindex]').forEach((node) => {
            node.setAttribute('tabindex', '-1');
        });
    };

    stripTabindex();

    const observer = new MutationObserver(() => stripTabindex());
    observer.observe(mapEl, { childList: true, subtree: true });
}