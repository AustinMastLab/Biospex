{{-- Opens with the modal: its Progress link's data-event is the event's slug (none for the current event). Polls every five minutes while open. --}}
<div x-data x-init="
    const dialog = $el.closest('dialog');
    dialog.addEventListener('modal:show', (event) => $wire.open(event.detail.relatedTarget.dataset.event ?? null));
    dialog.addEventListener('modal:hidden', () => $wire.close());
">
    <div wire:loading.delay wire:target="open" class="loader mx-auto"></div>

    @if($isOpen)
        <div wire:poll.300s wire:loading.remove wire:target="open">
            @include('common.wedigbio-progress-content', ['weDigBioEvent' => $weDigBioEvent])
        </div>
    @endif
</div>
