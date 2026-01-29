<tr>
    <td>
        <a href="{{ route('admin.geolocate-community.destroy', [$community]) }}"
           class="prevent-default"
           title="{{ t('Delete Community') }}"
           aria-label="{{ t('Delete Community') }}"
           data-hover="tooltip"
           data-method="delete"
           data-confirm="confirmation"
           data-title="{{ t('Delete Community') }}?"
           data-content="{{ t('This will permanently delete the Community. Any communities with attached data sources cannot be deleted.') }}">
            <i class="fas fa-trash-alt" aria-hidden="true"></i>
        </a>
    </td>
    <td>{{ $community->name }}</td>
    <td>
        @if($community->geoLocateDataSources->count() > 0)
            {{ t('Yes') }}
        @else
            {{ t('No') }}
        @endif
    </td>
</tr>