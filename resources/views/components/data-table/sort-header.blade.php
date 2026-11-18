{{-- A column header that sorts a Livewire table using App\Traits\WithDataTable. --}}
@props(['field', 'label', 'sortField', 'sortDirection'])

@php
    $isSorted = $sortField === $field;
    $ariaSort = $isSorted ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
    $icon = $isSorted ? ($sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort';
@endphp

<th scope="col" aria-sort="{{ $ariaSort }}" {{ $attributes }}>
    <button type="button"
            class="data-table-sort"
            wire:click="sortBy('{{ $field }}')"
            title="{{ t('Sort by %s', $label) }}">
        {{ $label }}
        <i class="fas {{ $icon }} {{ $isSorted ? '' : 'text-muted' }}" aria-hidden="true"></i>
    </button>
</th>
