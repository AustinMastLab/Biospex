<div>
    <div class="row">
        <div class="col-md-6 mx-auto mb-4 text-center">
            <button type="button"
                    wire:click="sortBy('title')"
                    class="sort-page mr-2 text-uppercase {{ $sort === 'title' ? 'active' : '' }}"
                    aria-label="{{ t('Sort projects by Title') }}">
                <i class="fas fa-{{ $sort === 'title' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i> {{ t('Title') }}
            </button>

            <button type="button"
                    wire:click="sortBy('group')"
                    class="sort-page ml-2 text-uppercase {{ $sort === 'group' ? 'active' : '' }}"
                    aria-label="{{ t('Sort projects by Group') }}">
                <i class="fas fa-{{ $sort === 'group' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i> {{ t('Group') }}
            </button>

            <button type="button"
                    wire:click="sortBy('date')"
                    class="sort-page ml-2 text-uppercase {{ $sort === 'date' ? 'active' : '' }}"
                    aria-label="{{ t('Sort projects by Date') }}">
                <i class="fas fa-{{ $sort === 'date' ? ($order === 'asc' ? 'sort-up' : 'sort-down') : 'sort' }}" aria-hidden="true"></i> {{ t('Date') }}
            </button>
        </div>
    </div>
    <div id="projects" class="row col-sm-12 mx-auto justify-content-center">
        @foreach($projects as $project)
            @include('front.project.partials.project-loop', ['project' => $project])
        @endforeach
    </div>
</div>
