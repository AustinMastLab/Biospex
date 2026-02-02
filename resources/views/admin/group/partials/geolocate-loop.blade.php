<tr>
    <td>
        <a href="{{ route('admin.groups-geolocate-form.destroy', [$group, $form]) }}"
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
            <a href="{{ route('admin.expeditions.show', [$expedition]) }}">{{ $expedition->id }}</a>
            @if(!$loop->last)
                {{ ', ' }}
            @endif
        @endforeach
    </td>
</tr>
