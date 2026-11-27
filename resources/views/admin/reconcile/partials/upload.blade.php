{{-- Upload Reconciled With User Opinion, loaded into the global modal (resources/js/admin-modals.js). --}}
<div class="col-md-10 mx-auto mt-3 mb-3">
    <p class="text-justify">
        {{ t('The form below provides a means to upload a user reviewed CSV file generated from the "Reconciled" file or
            "Reconciled With Expert Opinion" file located in the Expedition Download section. The file must be in CSV
            format and columns should match those in the "Reconciled" or "Reconciled With Expert Opinion" CSV files.') }}
    </p>
    @livewire('admin.reconcile-user-upload', ['expedition' => $expedition])
</div>
