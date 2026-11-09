{{-- Chart built when the modal opens; see resources/js/amChartWeDigBioRate.js. --}}
<x-modal id="wedigbio-rate-modal" :title="t('WEDIGBIO RATE')" size="modal-xl">
    <x-slot:subtitle>
        <span class="text-danger pl-2">{{ t('updates every 5 minutes') }}</span>
    </x-slot:subtitle>

    <div class="jumbotron box-shadow m-5">
        <div id="weDigBioRateChartDiv" class="d-flex justify-content-center" style="width:100%; height: 500px"></div>
        {{-- The chart's legend; it grows with the number of projects, so the chart keeps its height. --}}
        <div id="weDigBioRateLegendDiv" style="width:100%"></div>
    </div>
</x-modal>
