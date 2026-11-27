{{-- The expedition's GeoLocate community and data source (App\Livewire\Admin\GeoLocateCommunityForm). --}}
<form id="geolocate-community-form" wire:submit="save" role="form">
    <div class="form-row justify-content-center">
        <div class="form-group col-sm-6">
            <label for="community-form-select" class="col-form-label">{{ t('GeoLocate Community') }}:</label>
            <select id="community-form-select" class="form-control custom-select select-primary"
                    wire:model.live="communityId">
                <option value="" class="text-uppercase">{{ t('New') }}</option>
                @foreach($communities as $option)
                    <option value="{{ $option->id }}" wire:key="community-{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @if($communityId === '')
        <div id="community-row" class="form-row justify-content-center">
            <div class="form-group col-sm-6">
                <label id="community-label" for="community" class="col-form-label required">{{ t('New GeoLocate Community') }}:</label>
                <input type="text" id="community" wire:model="community"
                       @class(['w-100', 'form-control', 'a11y-form-control', 'is-invalid' => $errors->has('community')])
                       placeholder="{{ t('Enter new community name') }}"
                       @error('community') aria-describedby="community-error" aria-invalid="true" @enderror
                       required>
                @error('community')
                    <span id="community-error" class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>
    @endif
    <div class="form-row justify-content-center">
        <div class="form-group col-sm-6">
            <label for="data-source" class="col-form-label required">{{ t('Data Source') }}:</label>
            <input type="text" id="data-source" wire:model="dataSource"
                   @class(['w-100', 'form-control', 'a11y-form-control', 'is-invalid' => $errors->has('dataSource')])
                   placeholder="{{ t('Enter data source') }}"
                   @error('dataSource') aria-describedby="data-source-error" aria-invalid="true" @enderror
                   required>
            @error('dataSource')
                <span id="data-source-error" class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    @error('communityId')
        <p class="text-danger text-center">{{ $message }}</p>
    @enderror
    <div class="form-row justify-content-center">
        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ t('Submit') }}</button>
    </div>
</form>
