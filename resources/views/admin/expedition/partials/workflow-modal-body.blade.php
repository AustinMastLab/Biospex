<div class="col-md-10 mx-auto mt-3 mb-3">
    <form id="workflow-id-form" class="modal-form" method="post"
          action="{{ route('admin.panoptes-workflow.update', [$expedition]) }}"
          role="form">
        @csrf
        <div class="dfelx form-row align-items-center">
            <div class="col-10">
                <input type="text" name="panoptes_workflow_id" id="panoptes_workflow_id"
                       class="form-control {{ ($errors->has('panoptes_workflow_id')) ? 'has-error' : '' }} a11y-form-control"
                       placeholder="{{ t('Enter Workflow Id after Expedition submitted to Zooniverse') }}"
                       value="{{ old('panoptes_workflow_id', $expedition->panoptesProject->panoptes_workflow_id ?? '') }}"
                       required/>
            </div>
            <div class="col-2">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>
</div>
