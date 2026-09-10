<div>
    <div class="col-md-6 mx-auto mb-4 text-center">
            <button type="button"
                    wire:click="sortBy('title')"
                    wire:loading.attr="disabled"
                    wire:target="sortBy"
                    class="sort-page mr-2 text-uppercase {{ $sort === 'title' ? 'active' : '' }}"
                    aria-label="{{ t('Sort projects by Title') }}">
                <span class="mr-1 d-none"
                      wire:loading.class.remove="d-none"
                      wire:target="sortBy"
                      aria-hidden="true">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <i class="fas fa-{{ $sort === 'title' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i>
                {{ t('Title') }}
            </button>

            <button type="button"
                    wire:click="sortBy('group')"
                    wire:loading.attr="disabled"
                    wire:target="sortBy"
                    class="sort-page ml-2 text-uppercase {{ $sort === 'group' ? 'active' : '' }}"
                    aria-label="{{ t('Sort projects by Group') }}">
                <span class="mr-1 d-none"
                      wire:loading.class.remove="d-none"
                      wire:target="sortBy"
                      aria-hidden="true">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <i class="fas fa-{{ $sort === 'group' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i>
                {{ t('Group') }}
            </button>

            <button type="button"
                    wire:click="sortBy('date')"
                    wire:loading.attr="disabled"
                    wire:target="sortBy"
                    class="sort-page ml-2 text-uppercase {{ $sort === 'date' ? 'active' : '' }}"
                    aria-label="{{ t('Sort projects by Date') }}">
                <span class="mr-1 d-none"
                      wire:loading.class.remove="d-none"
                      wire:target="sortBy"
                      aria-hidden="true">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <i class="fas fa-{{ $sort === 'date' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i>
                {{ t('Date') }}
            </button>
    </div>
    <div id="projects" class="row col-sm-12 mx-auto justify-content-center">
        @forelse($records as $project)
            @include('front.project.partials.project-loop', ['project' => $project])
        @empty
            <h2 class="mx-auto pt-4">{{ t('No Projects exist.') }}</h2>
        @endforelse
    </div>

    @if($hasMore)
        <div wire:key="project-load-more-{{ $page }}"
             wire:intersect.once="loadMore"
             class="py-4">
            <div wire:loading
                 wire:target="loadMore"
                 class="w-100 text-center"
                 style="display: none;"
                 role="status"
                 aria-live="polite">
                <div class="loader d-inline-block">
                    <span class="sr-only">{{ t('Loading projects') }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
