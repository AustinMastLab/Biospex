{{-- Chart built when the modal opens; see resources/js/charts/wedigbio-rate-chart.js. --}}
<x-modal id="wedigbio-rate-modal" :title="t('WEDIGBIO RATE')" size="modal-xl">
    <x-slot:subtitle>
        <span class="text-danger pl-2">{{ t('updates every 5 minutes') }}</span>
    </x-slot:subtitle>

    <div class="jumbotron box-shadow m-5">
        <div id="weDigBioRateChartDiv" aria-hidden="true" style="width: 100%; height: 500px"></div>
        <details class="mt-3">
            <summary>{{ t('WeDigBio rate data table') }}</summary>
            <div id="weDigBioRateTable" class="mt-2"></div>
        </details>
    </div>
</x-modal>
