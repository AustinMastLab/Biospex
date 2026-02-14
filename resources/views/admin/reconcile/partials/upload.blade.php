<div class="col-md-10 mx-auto mt-3 mb-3">
    <p class="text-justify">
        {{ t('The form below provides a means to upload a user reviewed CSV file generated from the "Reconciled" file or
            "Reconciled With Expert Opinion" file located in the Expedition Download section. The file must be in CSV
            format and columns should match those in the "Reconciled" or "Reconciled With Expert Opinion" CSV files.') }}
    </p>
    <form id="uploadForm" class="modal-form" method="post"
          action="{{ route('admin.reconcile-user-form.upload', [$expedition]) }}"
          enctype="multipart/form-data"
          role="form">
        @csrf
        <div class="form-row justify-content-center">
            <div class="form-group col-sm-10">
                <div class="custom-file">
                    <label for="file" class="custom-file-label">{{ t('Upload Reconciled With User Opinion') }}</label>
                    <input type="file"
                           class="form-control custom-file-input mb-4 {{ ($errors->has('file')) ? 'is-invalid' : '' }} a11y-form-control"
                           name="file" accept=".csv">
                </div>
            </div>
        </div>
        <div class="form-row justify-content-center">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
        <div class="feedback text-center mt-3"></div>
    </form>
</div>