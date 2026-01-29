<div class="d-flex justify-content-center col-sm-12 mb-4">
    <span data-type="{{ $type }}" data-sort="title" data-order="asc"
          data-id="{{ isset($id) ? $id : null }}"
          data-url="{{ $route }}"
          data-target="{{ $type }}-expeditions" class="sort-page mr-2 text-uppercase"
          style="cursor: pointer; display: inline-block">
        <i class="fas fa-sort"></i> {{ t('Title') }}</span>
    @if( strpos(Route::currentRouteName(), 'admin') > -1)
    <span data-type="{{ $type }}" data-sort="project" data-order="asc"
          data-id="{{ isset($id) ? $id : null }}"
          data-url="{{ $route }}"
          data-target="{{ $type }}-expeditions" class="sort-page ml-2 text-uppercase"
          style="cursor: pointer; display: inline-block">
        <i class="fas fa-sort"></i> {{ t('Project') }}</span>
    @endif
    <span data-type="{{ $type }}" data-sort="date" data-order="asc"
          data-id="{{ isset($id) ? $id : null }}"
          data-url="{{ $route }}"
          data-target="{{ $type }}-expeditions" class="sort-page ml-2 text-uppercase"
          style="cursor: pointer; display: inline-block">
        <i class="fas fa-sort"></i> {{ t('Date') }}</span>
</div>