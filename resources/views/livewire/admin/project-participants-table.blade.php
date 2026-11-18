<div class="w-100">
    <x-data-table.controls id="transcribers-tbl" :search-label="t('Search participants')" :per-page-options="$this->perPageOptions()"/>
    <div class="table-responsive">
        <table id="transcribers-tbl" class="table table-striped table-bordered data-table">
            <thead>
            <tr>
                <x-data-table.sort-header field="user_name" :label="t('User')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
                <x-data-table.sort-header field="expeditionCount" :label="t('Expeditions')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
                <x-data-table.sort-header field="transcriptionCount" :label="t('Digitizations')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
                <x-data-table.sort-header field="last_date" :label="t('Last Date')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $participant)
                <tr wire:key="participant-{{ $participant['user_name'] }}">
                    <td>{{ $participant['user_name'] }}</td>
                    <td>{{ $participant['expeditionCount'] }}</td>
                    <td>{{ $participant['transcriptionCount'] }}</td>
                    <td>{{ $participant['last_date'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">{{ t('No matching records found') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-data-table.footer :paginator="$rows" :label="t('Participants pages')"/>
</div>
