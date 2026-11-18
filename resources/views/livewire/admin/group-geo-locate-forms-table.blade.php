<div class="w-100">
    <x-data-table.controls id="geolocate-tbl" :search-label="t('Search GeoLocateExport forms')" :per-page-options="$this->perPageOptions()"/>
    <div class="table-responsive">
        <table id="geolocate-tbl" class="table table-striped table-bordered data-table">
            <thead>
            <tr>
                <th scope="col" style="width: 5%"><span class="sr-only">{{ t('Delete') }}</span></th>
                <x-data-table.sort-header field="name" :label="t('Name')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
                <x-data-table.sort-header field="expeditions_count" :label="t('# Assigned Expeditions')" :sort-field="$sortField" :sort-direction="$sortDirection"/>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $form)
                <tr wire:key="form-{{ $form->id }}">
                    <td>
                        <a href="{{ route('admin.groups-geolocate-form.destroy', [$groupUuid, $form]) }}"
                           class="prevent-default"
                           title="{{ t('Delete GeoLocateExport Form') }}"
                           aria-label="{{ t('Delete GeoLocateExport Form') }}"
                           data-hover="tooltip"
                           data-method="delete"
                           data-confirm="confirmation"
                           data-title="{{ t('Delete GeoLocateExport Form') }}?"
                           data-content="{{ t('This will permanently delete the form') }}">
                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            <span class="sr-only">{{ t('Delete GeoLocateExport Form') }}</span>
                        </a>
                    </td>
                    <td>{{ $form->name }}</td>
                    <td>
                        @foreach($form->expeditions as $expedition)
                            <a href="{{ route('admin.expeditions.show', [$expedition]) }}">{{ $expedition->id }}</a>@if(! $loop->last), @endif
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">{{ t('No matching records found') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-data-table.footer :paginator="$rows" :label="t('GeoLocateExport forms pages')"/>
</div>
