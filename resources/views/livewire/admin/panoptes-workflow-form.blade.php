{{-- The expedition's Zooniverse workflow id (App\Livewire\Admin\PanoptesWorkflowForm). --}}
<div class="col-md-10 mx-auto mt-3 mb-3">
    <form id="workflow-id-form" wire:submit="save" role="form">
        <div class="form-row align-items-center">
            <div class="col-12 col-sm-9">
                <label for="panoptes_workflow_id" class="sr-only">{{ t('Workflow Id') }}</label>
                <input type="text" inputmode="numeric" id="panoptes_workflow_id" wire:model="panoptesWorkflowId"
                       @class(['form-control', 'a11y-form-control', 'is-invalid' => $errors->has('panoptesWorkflowId')])
                       placeholder="{{ t('Enter Workflow Id after Expedition submitted to Zooniverse') }}"
                       @error('panoptesWorkflowId') aria-describedby="panoptes_workflow_id-error" aria-invalid="true" @enderror
                       required/>
                @error('panoptesWorkflowId')
                    <span id="panoptes_workflow_id-error" class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-12 col-sm-3 mt-2 mt-sm-0 text-center text-sm-left">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ t('Submit') }}</button>
            </div>
        </div>
    </form>
</div>
