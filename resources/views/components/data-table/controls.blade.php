{{-- "Show N entries" and the search box for a Livewire table using App\Traits\WithDataTable. --}}
@props(['id', 'searchLabel', 'perPageOptions' => [10, 25, 50, 100]])

<div class="data-table-controls d-flex flex-wrap justify-content-between align-items-center mb-2">
    <label for="{{ $id }}-per-page" class="mb-1">
        {{ t('Show') }}
        <select id="{{ $id }}-per-page"
                class="custom-select custom-select-sm d-inline-block w-auto mx-1"
                wire:model.live="perPage">
            @foreach($perPageOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
        {{ t('entries') }}
    </label>
    <label for="{{ $id }}-search" class="mb-1">
        {{ t('Search') }}:
        <input type="search"
               id="{{ $id }}-search"
               class="form-control form-control-sm d-inline-block w-auto ml-1"
               autocomplete="off"
               aria-label="{{ $searchLabel }}"
               wire:model.live.debounce.300ms="search">
    </label>
</div>
