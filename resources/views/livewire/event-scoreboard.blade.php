{{-- Opens with the modal: its Scoreboard button's data-event is the event's uuid. Polls every five minutes while open. --}}
<div x-data x-init="
    const dialog = $el.closest('dialog');
    dialog.addEventListener('modal:show', (event) => $wire.open(event.detail.relatedTarget.dataset.event));
    dialog.addEventListener('modal:hidden', () => $wire.close());
">
    <div wire:loading.delay wire:target="open" class="loader mx-auto"></div>

    @if($eventUuid !== null)
        <div wire:poll.300s wire:loading.remove wire:target="open">
            @include('common.scoreboard-content', ['event' => $event])
        </div>
    @endif
</div>
