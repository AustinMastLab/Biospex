<div class="w-100">
    <x-data-table.controls id="expeditions-tbl" :search-label="t('Search expeditions')" :per-page-options="$this->perPageOptions()"/>
    <div class="table-responsive">
        <table id="expeditions-tbl" class="table table-striped table-bordered data-table">
            <thead>
            <tr>
                <x-data-table.sort-header field="title" :label="t('Title')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
                <x-data-table.sort-header field="completed" :label="t('Status')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $expedition)
                <tr wire:key="expedition-{{ $expedition->id }}">
                    <td><a href="{{ route('admin.expeditions.show', [$expedition]) }}">{{ $expedition->title }}</a></td>
                    <td>{{ $expedition->present()->completed }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center">{{ t('No matching records found') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-data-table.footer :paginator="$rows" :label="t('Expeditions pages')"/>
</div>
