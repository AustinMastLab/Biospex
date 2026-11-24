{{-- Chart built when the modal opens; see resources/js/charts/event-rate-chart.js. --}}
<x-modal id="step-chart-modal" :title="t('Rate Chart')" title-id="stepChartModalTitle" size="modal-xl">
    <div class="jumbotron box-shadow m-5">
        <div id="chartdiv" aria-hidden="true" style="width: 100%; height: 500px"></div>
        <details class="mt-3">
            <summary>{{ t('Rate chart data table') }}</summary>
            <div id="event-rate-chart-table" class="mt-2"></div>
        </details>
    </div>
</x-modal>
