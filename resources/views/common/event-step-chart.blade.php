{{-- Chart built when the modal opens; see resources/js/amChartEventRate.js. --}}
<x-modal id="step-chart-modal" :title="t('Rate Chart')" title-id="stepChartModalTitle" size="modal-xl">
    <div class="jumbotron box-shadow m-5">
        <div
            id="chartdiv"
            class="d-flex"
            style="width:100%; height: 500px"
            role="img"
            aria-label="Rate chart"
            aria-describedby="event-rate-chart-desc"
        ></div>
        <p id="event-rate-chart-desc" class="sr-only">
            Line chart showing estimated records per hour over time by team. The chart updates periodically.
        </p>
    </div>
</x-modal>
