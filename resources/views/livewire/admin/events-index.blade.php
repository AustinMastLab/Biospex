<div>
    <div class="col-md-8 mx-auto mb-4 text-center">
        <div class="mb-3">
            <button type="button"
                    class="sort-page mr-2 text-uppercase"
                    wire:click="sortBy('title')"
                    aria-label="{{ t('Sort events by Title') }}">
                <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Title') }}
            </button>

            <button type="button"
                    class="sort-page ml-2 text-uppercase"
                    wire:click="sortBy('project')"
                    aria-label="{{ t('Sort events by Project') }}">
                <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Project') }}
            </button>

            <button type="button"
                    class="sort-page ml-2 text-uppercase"
                    wire:click="sortBy('date')"
                    aria-label="{{ t('Sort events by Date') }}">
                <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Date') }}
            </button>
        </div>
    </div>

    <div id="{{ $this->type === 'completed' ? 'completed-events' : 'active-events' }}" class="row col-sm-12 mx-auto justify-content-center">
        @include('admin.event.partials.event', ['events' => $events])
    </div>
</div>
