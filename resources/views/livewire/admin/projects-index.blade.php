<div>
    <div class="col-md-6 mx-auto mb-4 text-center">
        <button type="button"
                class="sort-page mr-2 text-uppercase"
                wire:click="sortBy('title')"
                aria-label="{{ t('Sort projects by Title') }}">
            <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Title') }}
        </button>

        <button type="button"
                class="sort-page ml-2 text-uppercase"
                wire:click="sortBy('group')"
                aria-label="{{ t('Sort projects by Group') }}">
            <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Group') }}
        </button>

        <button type="button"
                class="sort-page ml-2 text-uppercase"
                wire:click="sortBy('date')"
                aria-label="{{ t('Sort projects by Date') }}">
            <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Date') }}
        </button>
    </div>

    <div id="projects" class="row col-sm-12 mx-auto justify-content-center">
        @include('admin.project.partials.project', ['projects' => $projects])
    </div>
</div>
