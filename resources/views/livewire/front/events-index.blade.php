<div>
    <div class="col-md-8 mx-auto mb-4 text-center">
        <div class="mb-4">
            <button type="button"
                    class="toggle-view-btn btn btn-primary text-uppercase"
                    wire:click="setType('{{ $type === 'active' ? 'completed' : 'active' }}')"
                    wire:loading.attr="disabled"
                    wire:target="setType">
                {{ $type === 'active' ? t('view completed events') : t('view active events') }}
            </button>
            <div wire:loading
                 wire:target="setType"
                 class="mt-2"
                 style="display: none;"
                 role="status">
                <i class="fas fa-spinner fa-spin color-action" aria-hidden="true"></i>
                <span class="sr-only">{{ t('Loading events') }}</span>
            </div>
        </div>

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

            @if($projectId === null)
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
            @endif

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

    <div id="{{ $type === 'completed' ? 'completed-events' : 'active-events' }}" class="row col-sm-12 mx-auto justify-content-center">
        @forelse($records as $event)
            @include('front.event.partials.event-loop')
        @empty
            <h2 class="mx-auto pt-4">{{ t('No Events exist.') }}</h2>
        @endforelse
    </div>

    @if($hasMore)
        <div wire:key="event-load-more-{{ $page }}"
             wire:intersect.once="loadMore"
             class="py-4">
            <div wire:loading
                 wire:target="loadMore"
                 class="w-100 text-center"
                 style="display: none;"
                 role="status"
                 aria-live="polite">
                <div class="loader d-inline-block">
                    <span class="sr-only">{{ t('Loading events') }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
