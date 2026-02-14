<form action="{{ route('admin.geolocates.store', [$expedition]) }}" method="post"
      role="form" id="geolocate-form">
    @csrf
    <input type="hidden" id="group_id" name="group_id" value="{{ $form['group_id'] }}">
    <input type="hidden" id="form_data" name="form_data" value="{{ !($form['fields'] === null) }}">
    <input type="hidden" id="entries" name="entries"
           value="{{ old('entries', isset($form['entries'])) ? $form['entries'] : 0 }}">

    <div class="form-group col-sm-10 mx-auto text-center">
        <h3 class="ml-auto mr-auto mb-3">{{ t('Select CSV Source') }}:</h3>
        @if(!$form['expert_reconciled'] && $form['expert_review'])
            <div class="col-sm-8 mb-3 ml-auto mr-auto text-center text-danger">{{ t('Reconciled Expert Review exists but csv file is not published.') }}</div>
        @endif
    </div>
    <div class="form-group col-sm-10 mx-auto text-center">
        <div class="form-row mt-2 entry">
            <select id="geolocate-source-select" class="select selectpicker mx-auto" name="source"
                    title="{{ t('GeoLocateExport CSV Source') }}"
                    data-header="{{ t('Select CSV source') }}"
                    data-width="350"
                    data-hide-disabled="true"
                    data-style="btn-primary"
                    data-url="{{ route('admin.geolocate-field.index', [$expedition]) }}"
                    required>
                <option value="reconciled"
                        {{ $form['source'] === 'reconciled' ? 'selected' : '' }}>{{ t('Reconciled') }}</option>
                <option value="reconciled-with-expert"
                        {{ $form['source'] === 'reconciled-with-expert' ? 'selected' : '' }}
                        {{ $form['expert_reconciled'] && $form['expert_review'] ? '' : 'disabled' }}>
                    {{ t('Reconciled With Expert Opinion') }}</option>
                <option value="reconciled-with-user"
                        {{ $form['source'] === 'reconciled-with-user' ? 'selected' : '' }}
                        {{ $form['user_reconciled'] ? '' : 'disabled' }}>{{ t('Reconciled With User Opinion') }}</option>
                <option value="upload">{{ t('Upload Reconciled With User Opinion') }}</option>
            </select>
        </div>
        <button id="user-upload" style="display: none"
                data-dismiss="modal"
                data-toggle="modal"
                data-target="#global-modal"
                data-size="modal-lg"
                data-url="{{ route('admin.reconcile-user-form.edit', [$expedition]) }}"
                data-title="{{ t('Upload Reconciled With User Opinion') }}"></button>
    </div>
    <div class="form-group col-sm-10 mx-auto text-center">
        <div class="form-row col-sm-6 m-auto mt-4 text-justify">
            {{ t('It is suggested to use the Reconciled Expert Review for GeoLocateExport. If one does not exist, you can start the procedure in the Expedition tools menu. You may also upload a Reconciled file you reviewed yourself.') }}
        </div>
    </div>
    <div class="form-group col-sm-10 mx-auto text-center">
        <div class="form-row col-sm-6 m-auto mt-4 text-justify">
            <label for="name" class="col-form-label font-bold required">{{ t('Form Name') }}:</label>
            <input type="text" class="form-control {{ ($errors->has('name')) ? 'is-invalid' : '' }} a11y-form-control"
                   id="name" name="name"
                   value="{{ $form['name'] }}" required>
            <span class="invalid-feedback">{{ $errors->first('name') }}</span>
        </div>
    </div>

    <div class="form-group col-sm-10 mx-auto text-center">
        <div id="geolocate-fields">
            @livewire('geolocate-field-manager', [
                'geoOptions' => $form['geo'],
                'csvOptions' => $form['csv'],
                'existingFields' => $form['fields'] ?? [],
                'expedition' => $expedition
            ])
        </div>
        <div class="row mt-3">
            <div id="warning" class="col-sm-10 mx-auto text-center text-danger collapse"></div>
        </div>
        <div class="row mt-3 justify-content-center">
            <button type="submit"
                    class="btn btn-primary pl-4 pr-4 mt-5 text-uppercase m-auto" {{ $disabled ? '' : '' }}>{{ t('Save') }}</button>
            @if($form['fields'])
                <button type="button" id="export"
                        data-url="{{ route('admin.geolocate-export.index', [$expedition]) }}"
                        class="btn btn-primary pl-4 pr-4 mt-5 text-uppercase m-auto" {{ $disabled ? 'disabled' : '' }}>{{ t('Export') }}</button>
            @endif
        </div>
    </div>
</form>
<div class="row default" style="display: none">
    <div class="col-sm-6 mt-3">
        <select class="geolocate-field-default" name="fields[999][geo]"
                data-live-search="true"
                data-actions-box="true"
                data-header="{{ t('Select GeoLocate Field ( * required)') }}"
                data-width="80%"
                data-style="btn-primary">
            <option value="">{{ t('None') }}</option>
            @foreach($form['geo'] as $key => $value)
                <option value="{{ is_numeric($key) ? str_replace('*', '', $value) : $key }}">{{ $value }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 mt-3">
        <select class="header-select-default" name="fields[999][csv]"
                data-live-search="true"
                data-actions-box="true"
                data-header="{{ t('Select CSV Column') }}"
                data-width="80%"
                data-style="btn-primary">
            <option value="">{{ t('None') }}</option>
            @foreach($form['csv'] as $column)
                <option value="{{ $column }}">{{ $column }}</option>
            @endforeach
        </select>
    </div>
</div>
