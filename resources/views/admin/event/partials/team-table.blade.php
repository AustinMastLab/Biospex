<div class="col-md-10 offset-2 mx-auto">
    <h3 class="text-center pt-4">{{ t('Teams Summary') }}</h3>
    <hr>
    @if($event->teams->isEmpty())
        <p class="text-center">{{ t('No teams exist at this time.') }}</p>
    @else
        <livewire:admin.event-teams-table :event="$event"/>
    @endif
</div>
