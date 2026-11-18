<div>
    <x-data-table.controls id="teams-tbl" :search-label="t('Search teams')" :per-page-options="$this->perPageOptions()"/>
    <div class="table-responsive">
        <table id="teams-tbl" class="table table-striped table-bordered data-table">
            <thead>
            <tr>
                <x-data-table.sort-header field="team" :label="t('Teams')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
                <x-data-table.sort-header field="user" :label="t('Users')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
                <x-data-table.sort-header field="transcriptions_count" :label="t('Digitizations')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr wire:key="team-{{ $loop->index }}-{{ $row->team }}-{{ $row->user }}">
                    <td>{{ $row->team }}</td>
                    <td>{{ $row->user }}</td>
                    <td>{{ $row->transcriptions_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">{{ t('No matching records found') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-data-table.footer :paginator="$rows" :label="t('Teams pages')"/>
</div>
