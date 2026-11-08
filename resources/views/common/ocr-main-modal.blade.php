{{-- OcrModal opens it with $this->dispatch('open-modal', id: 'ocr-modal') once its content is loaded. --}}
<x-modal id="ocr-modal" :title="t('OCR Content')">
    @livewire('ocr-modal')
</x-modal>
