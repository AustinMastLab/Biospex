<div class="controls">
    @foreach($assets as $index => $asset)
        <div class="entry mb-4" wire:key="asset-{{ $index }}">
            <fieldset class="row border p-2">
                <legend class="w-auto">{{ t('Resources') }} {{ $index + 1 }}</legend>
                <div class="col-3">
                    <label class="col-form-label"
                           for="assets[{{ $index }}][type]">{{ t('Type') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text btn btn-primary px-3 py-0"
                                  wire:click="addAsset"
                                  style="cursor: pointer;">
                                <i class="fas fa-plus"></i>
                            </span>
                        </div>
                        <select name="assets[{{ $index }}][type]"
                                id="assets[{{ $index }}][type]"
                                wire:model.defer="assets.{{ $index }}.type"
                                class="form-control custom-select a11y-form-control {{ ($errors && isset($errors["assets.$index.type"])) ? 'is-invalid' : '' }}">
                            <option value="">None</option>
                            @foreach(config('config.project_assets') as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                            <option value="Delete">Delete</option>
                        </select>
                        @if(count($assets) > 1)
                            <div class="input-group-append">
                                <span class="input-group-text btn btn-danger px-3 py-0"
                                      wire:click="removeAsset({{ $index }})"
                                      style="cursor: pointer;">
                                    <i class="fas fa-minus"></i>
                                </span>
                            </div>
                        @endif
                        <span class="invalid-feedback">{{ $errors && isset($errors["assets.$index.type"]) ? $errors["assets.$index.type"][0] : '' }}</span>
                    </div>
                </div>
                <div class="col-4">
                    <label class="col-form-label"
                           for="assets[{{ $index }}][name]">{{ t('URL or Name') }}</label>
                    <input type="text"
                           class="form-control a11y-form-control {{ ($errors && isset($errors["assets.$index.name"])) ? 'is-invalid' : '' }}"
                           id="assets[{{ $index }}][name]"
                           name="assets[{{ $index }}][name]"
                           wire:model.defer="assets.{{ $index }}.name"
                           value="{{ old("assets.$index.name", $asset['name'] ?? '') }}">
                    <span class="invalid-feedback">{{ $errors && isset($errors["assets.$index.name"]) ? $errors["assets.$index.name"][0] : '' }}</span>
                </div>
                <div class="col-5">
                    <label class="col-form-label"
                           for="assets[{{ $index }}][description]">{{ t('Description') }}</label>
                    <input type="text"
                           class="form-control a11y-form-control {{ ($errors && isset($errors["assets.$index.description"])) ? 'is-invalid' : '' }}"
                           id="assets[{{ $index }}][description]"
                           name="assets[{{ $index }}][description]"
                           wire:model.defer="assets.{{ $index }}.description"
                           value="{{ old("assets.$index.description", $asset['description'] ?? '') }}">
                    <span class="invalid-feedback">{{ $errors && isset($errors["assets.$index.description"]) ? $errors["assets.$index.description"][0] : '' }}</span>
                </div>
                <div class="col-6 mt-2 mx-auto">
                    @livewire('file-upload', [
                        'modelType' => 'ProjectAsset',
                        'fieldName' => 'download_' . $index,
                        'maxSize' => 10240,
                        'allowedTypes' => ['txt', 'doc', 'docx', 'csv', 'pdf'],
                        'projectUuid' => $projectUuid ?? null
                    ], 'file-upload-' . $index)
                </div>
                <input type="hidden"
                       id="assets[{{ $index }}][id]"
                       name="assets[{{ $index }}][id]"
                       wire:model.defer="assets.{{ $index }}.id"
                       value="{{ old("assets.$index.id", $asset['id'] ?? '') }}">

                <!-- Hidden input for download_path to be submitted with the form -->
                <input type="hidden"
                       name="assets[{{ $index }}][download_path]"
                       value="{{ $asset['download_path'] ?? '' }}">
            </fieldset>
        </div>
    @endforeach
</div>