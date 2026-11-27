{{-- GeoLocate Export Form (App\Livewire\Admin\GeoLocateExportForm). --}}
<div>
    @if($loadError !== '')
        <p class="text-danger text-center mt-3" role="alert">{{ $loadError }}</p>
    @else
        <div class="row">
            <div class="col-sm-10 mx-auto mt-3 text-center">
                <select id="geolocate-form-select" class="form-control custom-select select-primary mx-auto"
                        wire:model.live="formId"
                        aria-label="{{ t('Select New or Saved Form') }}"
                        style="max-width: 350px">
                    <option value="" class="text-uppercase">{{ t('New') }}</option>
                    @foreach($savedForms as $savedForm)
                        <option value="{{ $savedForm->id }}" wire:key="saved-form-{{ $savedForm->id }}">{{ $savedForm->name }}: {{ $savedForm->created_at }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <form id="geolocate-form" class="mt-5" wire:submit="save" role="form">
            <div class="form-group col-sm-10 mx-auto text-center">
                <h3 class="mx-auto mb-3">{{ t('Select CSV Source') }}:</h3>
                @if(! $expertReconciled && $expertReview)
                    <div class="col-sm-8 mb-3 mx-auto text-center text-danger">{{ t('Reconciled Expert Review exists but csv file is not published.') }}</div>
                @endif
                <select id="geolocate-source-select" class="form-control custom-select select-primary mx-auto"
                        wire:model.live="source"
                        aria-label="{{ t('GeoLocateExport CSV Source') }}"
                        style="max-width: 350px"
                        required>
                    <option value="reconciled">{{ t('Reconciled') }}</option>
                    @if(in_array('reconciled-with-expert', $sources, true))
                        <option value="reconciled-with-expert">{{ t('Reconciled With Expert Opinion') }}</option>
                    @endif
                    @if(in_array('reconciled-with-user', $sources, true))
                        <option value="reconciled-with-user">{{ t('Reconciled With User Opinion') }}</option>
                    @endif
                </select>
                {{-- Opens the upload form in the same modal. --}}
                <button type="button" id="user-upload" class="btn btn-link d-block mx-auto mt-2"
                        data-modal-target="#global-modal"
                        data-size="modal-lg"
                        data-url="{{ $uploadUrl }}"
                        data-title="{{ t('Upload Reconciled With User Opinion') }}">{{ t('Upload Reconciled With User Opinion') }}</button>
            </div>

            <div class="form-group col-sm-10 mx-auto text-center">
                <div class="form-row col-sm-6 m-auto mt-4 text-justify">
                    {{ t('It is suggested to use the Reconciled Expert Review for GeoLocateExport. If one does not exist, you can start the procedure in the Expedition tools menu. You may also upload a Reconciled file you reviewed yourself.') }}
                </div>
            </div>

            <div class="form-group col-sm-10 mx-auto text-center">
                <div class="form-row col-sm-6 m-auto mt-4 text-justify">
                    <label for="name" class="col-form-label font-bold required">{{ t('Form Name') }}:</label>
                    <input type="text" id="name" wire:model="name"
                           @class(['form-control', 'a11y-form-control', 'is-invalid' => $errors->has('name')])
                           @error('name') aria-describedby="name-error" aria-invalid="true" @enderror
                           required>
                    @error('name')
                        <span id="name-error" class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group col-sm-10 mx-auto text-center">
                <div class="row mt-5">
                    <div id="geolocate-fields-heading" class="col-sm-6 font-weight-bold">{{ t('GeoLocateExport Fields') }}</div>
                    <div id="csv-fields-heading" class="col-sm-6 font-weight-bold">{{ t('CSV Header Fields') }}</div>
                </div>
                <div class="row mt-3">
                    <div id="controls" class="col-sm-12">
                        @foreach($fields as $index => $field)
                            <div class="row entry" wire:key="field-{{ $index }}">
                                <div class="col-sm-6 mt-3">
                                    <select class="geolocate-field form-control custom-select select-primary"
                                            style="width: 80%"
                                            wire:model="fields.{{ $index }}.geo"
                                            aria-label="{{ t('GeoLocate field, row %s ( * required)', $index + 1) }}"
                                            @error('fields.'.$index.'.geo') aria-invalid="true" @enderror
                                            required>
                                        <option value="">{{ t('None') }}</option>
                                        @foreach($geoOptions as $key => $label)
                                            <option value="{{ $geoValues[$key] }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <select class="header-select form-control custom-select select-primary"
                                            style="width: 80%"
                                            wire:model="fields.{{ $index }}.csv"
                                            aria-label="{{ t('CSV column, row %s', $index + 1) }}"
                                            @error('fields.'.$index.'.csv') aria-invalid="true" @enderror
                                            required>
                                        <option value="">{{ t('None') }}</option>
                                        @foreach($csvOptions as $column)
                                            <option value="{{ $column }}">{{ $column }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-10 offset-sm-2 mt-5 text-left">
                        <button type="button" class="btn btn-primary pl-4 pr-4" wire:click="addField"
                                aria-label="{{ t('Add New Row') }}" data-hover="tooltip" title="{{ t('Add New Row') }}">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn btn-primary pl-4 pr-4" wire:click="removeField"
                                aria-label="{{ t('Delete Last Row') }}" data-hover="tooltip" title="{{ t('Delete Last Row') }}"
                                @disabled(count($fields) < 2)>
                            <i class="fas fa-minus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                {{-- The source's and rows' messages, once each (every row's missing field has the same message). --}}
                @php($fieldMessages = collect($errors->get('source'))->merge($errors->get('fields'))->merge(collect($errors->get('fields.*'))->flatten())->unique())
                <div id="warning" class="row mt-3" role="alert">
                    @if($fieldMessages->isNotEmpty())
                        <ul class="col-sm-10 mx-auto list-unstyled text-center text-danger mb-0">
                            @foreach($fieldMessages as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="row mt-3 justify-content-center">
                    <button type="submit" class="btn btn-primary pl-4 pr-4 mt-5 text-uppercase m-auto"
                            wire:loading.attr="disabled">{{ t('Save') }}</button>
                    @if($hasSavedForm)
                        <button type="button" id="export" wire:click="export"
                                class="btn btn-primary pl-4 pr-4 mt-5 text-uppercase m-auto"
                                wire:loading.attr="disabled" @disabled($exportDisabled)>{{ t('Export') }}</button>
                    @endif
                </div>
            </div>
        </form>
    @endif
</div>
