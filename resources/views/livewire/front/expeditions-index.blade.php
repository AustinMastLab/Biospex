<div>
    <div class="col-md-8 mx-auto mb-4 text-center">
        <div class="mb-3">
            <button type="button"
                    class="sort-page mr-2 text-uppercase"
                    wire:click="sortBy('title')"
                    wire:loading.attr="disabled"
                    wire:target="sortBy"
                    aria-label="{{ t('Sort expeditions by Title') }}">
                <span class="mr-1 d-none"
                      wire:loading.class.remove="d-none"
                      wire:target="sortBy"
                      aria-hidden="true">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <i class="fas fa-{{ $sort === 'title' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i> {{ t('Title') }}
            </button>

            @if( strpos(Route::currentRouteName(), 'admin') > -1)
                <button type="button"
                        class="sort-page ml-2 text-uppercase"
                        wire:click="sortBy('project')"
                        wire:loading.attr="disabled"
                        wire:target="sortBy"
                        aria-label="{{ t('Sort expeditions by Project') }}">
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
                    aria-label="{{ t('Sort expeditions by Date') }}">
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

    <div id="{{ $type === 'completed' ? 'completed-expeditions' : 'active-expeditions' }}" class="row col-sm-12 mx-auto justify-content-center">
        @include('front.expedition.partials.expedition', ['expeditions' => $expeditions])
    </div>
</div>
