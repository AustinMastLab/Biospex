<div class="col-md-6 mx-auto mb-4 text-center">
    <button type="button"
            data-sort="title" data-order="asc" data-url="{{ $route }}"
            data-target="projects"
            class="sort-page mr-2 text-uppercase"
            aria-label="{{ t('Sort projects by Title') }}">
        <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Title') }}
    </button>

    <button type="button"
            data-sort="group" data-order="asc" data-url="{{ $route }}"
            data-target="projects"
            class="sort-page ml-2 text-uppercase"
            aria-label="{{ t('Sort projects by Group') }}">
        <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Group') }}
    </button>

    <button type="button"
            data-sort="date" data-order="asc" data-url="{{ $route }}"
            data-target="projects"
            class="sort-page ml-2 text-uppercase"
            aria-label="{{ t('Sort projects by Date') }}">
        <i class="fas fa-sort" aria-hidden="true"></i> {{ t('Date') }}
    </button>
</div>