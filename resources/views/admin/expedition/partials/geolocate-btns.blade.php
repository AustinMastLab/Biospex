@use('App\Enums\ActorExpeditionState')
<hr class="header mx-auto" style="width:300px;">
<h4>{{ $actor->title }}</h4>

<button
        class="btn btn-primary rounded-0 mb-1"
        data-dismiss="modal"
        data-toggle="modal"
        data-target="#global-modal"
        data-size="modal-xl"
        data-url="{{ route('admin.geolocate-form.index', [$expedition]) }}"
        data-title="{{ t('GeoLocate Export Form') }}">{{ t('GeoLocate Export Form') }}</button>

@isset($expedition->geoLocateDataSource)
    <button type="button" id="deleteExport" class="btn btn-primary rounded-0 mb-1"
            data-href=" {{ route('admin.geolocates.destroy', [$expedition]) }}"
            data-hover="tooltip"
            data-method="delete"
            data-confirm="confirmation"
            title="{{ t('Disassociate Export Form from Expedition') }}"
            data-title="{{ t('Disassociate Export Form from Expedition') }}?"
            data-content="{{t('This will permanently delete the export file, the data source, and disassociate the Expedition from the GeoLocate Form. To delete a GeoLocateForm, please visit the Groups section of the site.') }}">
        {{ t('Disassociate Export Form from Expedition') }}</button>
@endisset

@if($expedition->project->group->geoLocateForms->isNotEmpty())
    <a href="{{ route('admin.groups.show', [$expedition->project->group]) }}#geolocate-forms"
       class="btn btn-primary rounded-0 mb-1">{{ t('Manage GeoLocate Forms') }}</a>
@endif

@if($actor->pivot->state > ActorExpeditionState::NotStarted->value)
    <a href="" class="prevent-default btn btn-primary rounded-0 mb-1"
       data-dismiss="modal"
       data-toggle="modal"
       data-target="#global-modal"
       data-size="modal-lg"
       data-url="{{ route('admin.geolocate-community.edit', [$expedition]) }}"
       data-title="{{ t('GeoLocate Community & Data Source Form') }}"> {{ t('GeoLocate Community & Data Source Form') }}</a>
@endif

@if($actor->pivot->state > ActorExpeditionState::Exported->value)
    <button
            class="btn btn-primary rounded-0 mb-1"
            data-dismiss="modal"
            data-toggle="modal"
            data-target="#global-modal"
            data-size="modal-lg"
            data-url="{{ route('admin.geolocate-stat.index', [$expedition]) }}"
            data-title="{{ t('GeoLocate Stats') }}">{{ t('GeoLocate Stats') }}</button>
@endif

@if($actor->pivot->state === ActorExpeditionState::Complete->value)
    <a href="{{ route('admin.geolocate-stat.update', [$expedition]) }}"
       class="prevent-default btn btn-primary rounded-0 mb-1"
       data-dismiss="modal"
       data-title="{{ t('Refresh GeoLocate Stats') }}"
       data-method="post"
       data-confirm="confirmation"
       data-content="{{ t('This will refresh the stats and kml file if there has been changes on GeoLocate. Do you wish to Continue?') }}
            ">{{ t('Refresh GeoLocate Stats') }}</a>
@endif