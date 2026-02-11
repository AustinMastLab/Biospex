<div>
    <div class="col-md-8 mx-auto mb-4 text-center">
        <div class="mb-3">
            <button type="button"
                    class="sort-page mr-2 text-uppercase"
                    wire:click="sortBy('title')"
                    aria-label="{{ t('Sort expeditions by Title') }}">
                <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Title') }}
            </button>

            @if( strpos(Route::currentRouteName(), 'admin') > -1)
                <button type="button"
                        class="sort-page ml-2 text-uppercase"
                        wire:click="sortBy('project')"
                        aria-label="{{ t('Sort expeditions by Project') }}">
                    <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Project') }}
                </button>
            @endif

            <button type="button"
                    class="sort-page ml-2 text-uppercase"
                    wire:click="sortBy('date')"
                    aria-label="{{ t('Sort expeditions by Date') }}">
                <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Date') }}
            </button>
        </div>
    </div>

    <div id="{{ $type === 'completed' ? 'completed-expeditions' : 'active-expeditions' }}" class="row col-sm-12 mx-auto justify-content-center">
        @include('front.expedition.partials.expedition', ['expeditions' => $expeditions])
    </div>
</div>
