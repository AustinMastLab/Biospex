{{-- Shown while the transcription chart loads; resources/js/amChartTranscript.js opens and closes it. --}}
<x-modal id="script-modal" :fade="false" body-class="text-center pb-5 pt-5" aria-labelledby="script-modal-label">
    <h2 id="script-modal-label">{{ t('Loading stats for visualizations. This will take a moment.') }}</h2>
    <div class="loader mx-auto"></div>
</x-modal>
