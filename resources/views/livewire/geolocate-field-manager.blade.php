<div>
    <div class="row mt-5">
        <div class="col-sm-6 font-weight-bold">
            {{ t('GeoLocateExport Fields') }}
        </div>
        <div class="col-sm-6 font-weight-bold">
            {{ t('CSV Header Fields') }}
        </div>
    </div>
    <div class="row mt-3">
        <div id="controls" class="controls col-sm-12">
            @foreach($fields as $index => $field)
                <div class="row entry" wire:key="field-{{ $index }}">
                    <div class="col-sm-6 mt-3">
                        <select class="geolocate-field form-control custom-select select-primary"
                                style="width: 80%"
                                wire:model="fields.{{ $index }}.geo"
                                name="fields[{{ $index }}][geo]"
                                aria-label="{{ t('Select GeoLocateExport Field ( * required)') }}"
                                required>
                            <option value="">{{ t('None') }}</option>
                            @foreach($geoOptions as $key => $value)
                                @php($optionValue = is_numeric($key) ? str_replace('*', '', $value) : $key)
                                <option value="{{ $optionValue }}" {{ $field['geo'] === $optionValue ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 mt-3">
                        <select class="header-select form-control custom-select select-primary"
                                style="width: 80%"
                                wire:model="fields.{{ $index }}.csv"
                                name="fields[{{ $index }}][csv]"
                                aria-label="{{ t('Select CSV Column') }}"
                                required>
                            <option value="">{{ t('None') }}</option>
                            @foreach($csvOptions as $column)
                                <option value="{{ $column }}" {{ $field['csv'] === $column ? 'selected' : '' }}>{{ $column }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="row">
        <div class="col-sm-10 offset-sm-2 mt-5 text-left">
            <button type="button" 
                    class="btn btn-primary pl-4 pr-4" 
                    wire:click="addField"
                    data-hover="tooltip"
                    title="{{ t('Add New Row') }}">
                <i class="fas fa-plus"></i>
            </button>
            <button type="button" 
                    class="btn btn-primary pl-4 pr-4" 
                    wire:click="removeField"
                    data-hover="tooltip"
                    title="{{ t('Delete Last Row') }}">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    @if($errorMessage)
        <div class="row mt-3">
            <div class="col-sm-10 mx-auto text-center text-danger">
                {{ $errorMessage }}
            </div>
        </div>
    @endif
    
    {{-- Hidden input to track entries count --}}
    <input type="hidden" id="entries" name="entries" value="{{ $entries }}">
</div>
