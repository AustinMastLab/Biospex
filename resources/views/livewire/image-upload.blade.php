<div>
    <div class="form-group">
        <label for="file"
               class="form-label">{{ $fieldName ? ucfirst(str_replace('_', ' ', $fieldName)) : 'Image' }}</label>
        <input type="file"
               wire:model="file"
               class="form-control @error('file') is-invalid @enderror a11y-form-control"
               id="file"
               accept="image/*">

        @error('file')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <!-- Upload Progress Indicator -->
        <div wire:loading wire:target="file" class="mt-2" style="display: none;" x-show="$wire.uploading">
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar" style="width: 100%">
                    <i class="fas fa-cloud-upload-alt"></i> Uploading to S3...
                </div>
            </div>
        </div>

        <!-- File Selection Feedback -->
        @if($file)
            <div class="mt-2">
                <div class="text-success mb-2">
                    <i class="fas fa-check-circle"></i>
                    <small>Image selected: {{ $file->getClientOriginalName() }}</small>
                </div>
                @if(!empty($variants))
                    <div class="text-info">
                        <small>Variants will be created: {{ implode(', ', array_keys($variants)) }}</small>
                    </div>
                @endif
            </div>
        @endif

        <!-- Upload Success Message -->
        @if($uploadSuccess)
            <div class="mt-2 alert alert-success alert-sm">
                <i class="fas fa-cloud-check"></i>
                Image uploaded successfully!
            </div>
        @endif

        <!-- Upload Error Message -->
        @if($uploadError)
            <div class="mt-2 alert alert-danger alert-sm">
                <i class="fas fa-exclamation-triangle"></i>
                Upload failed: {{ $uploadError }}
            </div>
        @endif

        <small class="form-text text-muted">
            Max size: {{ $maxSize / 1024 }}MB.
            Allowed types: {{ implode(', ', $allowedTypes) }}
        </small>
    </div>
</div>
