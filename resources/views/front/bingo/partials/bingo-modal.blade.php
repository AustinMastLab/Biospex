{{-- Winner message, filled in and opened by resources/js/amChartBingo.js. --}}
<x-modal id="bingo-modal" title="WINNER" title-id="bingoModalTitle" body-class="text-center" :backdrop="false">
    <x-slot:footer>
        <button type="button" class="btn btn-outline-primary color-action align-self-center"
                data-modal-dismiss>EXIT</button>
    </x-slot:footer>
</x-modal>
