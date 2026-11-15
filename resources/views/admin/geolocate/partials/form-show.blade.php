<div class="row">
    <div class="controls col-sm-10 mx-auto mt-3 text-center">
        <select id="geolocate-form-select" class="form-control custom-select select-primary mx-auto"
                name="geolocate-form-select"
                aria-label="{{ t('Select New or Saved Form') }}"
                style="max-width: 350px"
                data-url="{{ route('admin.geolocate-form.show', [$expedition]) }}">
            <option value="" class="text-uppercase">{{ t('New') }}</option>
            @foreach($expedition->project->group->geoLocateForms as $form)
                <option value="{{ $form->id }}" {{ $selectedForm === $form->id ? 'selected' : ''}}>
                    {{ $form->name }}: {{ $form->created_at }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row">
    <div id="geolocate-form-results" class="col-sm-12 text-center m-auto mt-5">{!! $formFields !!}</div>
</div>