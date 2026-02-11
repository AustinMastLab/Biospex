<div>
    <div class="col-md-8 mx-auto mb-4 text-center">
        <div class="mb-3">
            <button type="button"
                    class="sort-page mr-2 text-uppercase"
                    wire:click="sortBy('title')"
                    wire:loading.attr="disabled"
                    wire:target="sortBy"
                    aria-label="{{ t('Sort events by Title') }}">
                <span class="mr-1 d-none"
                      wire:loading.class.remove="d-none"
                      wire:target="sortBy"
                      aria-hidden="true">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <i class="fas fa-{{ $sort === 'title' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i> {{ t('Title') }}
            </button>

            <button type="button"
                    class="sort-page ml-2 text-uppercase"
                    wire:click="sortBy('project')"
                    wire:loading.attr="disabled"
                    wire:target="sortBy"
                    aria-label="{{ t('Sort events by Project') }}">
                <span class="mr-1 d-none"
                      wire:loading.class.remove="d-none"
                      wire:target="sortBy"
                      aria-hidden="true">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <i class="fas fa-{{ $sort === 'project' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i> {{ t('Project') }}
            </button>

            <button type="button"
                    class="sort-page ml-2 text-uppercase"
                    wire:click="sortBy('date')"
                    wire:loading.attr="disabled"
                    wire:target="sortBy"
                    aria-label="{{ t('Sort events by Date') }}">
                <span class="mr-1 d-none"
                      wire:loading.class.remove="d-none"
                      wire:target="sortBy"
                      aria-hidden="true">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <i class="fas fa-{{ $sort === 'date' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i> {{ t('Date') }}
            </button>
        </div>
    </div>

    <div id="{{ $this->type === 'completed' ? 'completed-events' : 'active-events' }}" class="row col-sm-12 mx-auto justify-content-center">
        @include('admin.event.partials.event', ['events' => $events])
    </div>
</div>
