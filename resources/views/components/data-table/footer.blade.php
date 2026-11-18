{{-- "Showing x to y of z entries" and the pager for a Livewire table using App\Traits\WithDataTable. --}}
@props(['paginator', 'label'])

@php
    $pageName = $paginator->getPageName();
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    // Up to seven buttons, as DataTables showed: first, last, and the pages around the current one.
    if ($last <= 7) {
        $pages = range(1, $last);
    } elseif ($current <= 4) {
        $pages = [1, 2, 3, 4, 5, null, $last];
    } elseif ($current >= $last - 3) {
        $pages = [1, null, $last - 4, $last - 3, $last - 2, $last - 1, $last];
    } else {
        $pages = [1, null, $current - 1, $current, $current + 1, null, $last];
    }
@endphp

<div class="data-table-footer d-flex flex-wrap justify-content-between align-items-center">
    <div class="mb-2" role="status" aria-live="polite">
        @if($paginator->total() === 0)
            {{ t('Showing 0 entries') }}
        @else
            {{ t('Showing %s to %s of %s entries', number_format($paginator->firstItem()), number_format($paginator->lastItem()), number_format($paginator->total())) }}
        @endif
    </div>

    @if($last > 1)
        <nav aria-label="{{ $label }}">
            <ul class="pagination pagination-sm mb-2">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    <button type="button" class="page-link" wire:click="previousPage('{{ $pageName }}')"
                            @disabled($paginator->onFirstPage())>{{ t('Previous') }}</button>
                </li>
                @foreach($pages as $page)
                    @if($page === null)
                        <li class="page-item disabled" aria-hidden="true"><span class="page-link">&hellip;</span></li>
                    @elseif($page === $current)
                        <li class="page-item active" aria-current="page">
                            <span class="page-link">{{ $page }}<span class="sr-only"> ({{ t('current page') }})</span></span>
                        </li>
                    @else
                        <li class="page-item">
                            <button type="button" class="page-link" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                    aria-label="{{ t('Page %s', $page) }}">{{ $page }}</button>
                        </li>
                    @endif
                @endforeach
                <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                    <button type="button" class="page-link" wire:click="nextPage('{{ $pageName }}')"
                            @disabled(! $paginator->hasMorePages())>{{ t('Next') }}</button>
                </li>
            </ul>
        </nav>
    @endif
</div>
