{{-- Upload Reconciled With User Opinion (App\Livewire\Admin\ReconcileUserUpload). --}}
<form id="uploadForm" wire:submit="save" role="form">
    <div class="form-row justify-content-center">
        <div class="form-group col-sm-10">
            <div class="custom-file">
                <label for="reconcile-user-file" class="custom-file-label">{{ t('Upload Reconciled With User Opinion') }}</label>
                <input type="file" id="reconcile-user-file" wire:model="file" accept=".csv"
                       @class(['form-control', 'custom-file-input', 'mb-4', 'a11y-form-control', 'is-invalid' => $errors->has('file')])
                       @error('file') aria-describedby="reconcile-user-file-error" aria-invalid="true" @enderror>
                @error('file')
                    <span id="reconcile-user-file-error" class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
    <div class="form-row justify-content-center">
        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="file, save">{{ t('Submit') }}</button>
    </div>
</form>
