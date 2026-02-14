@for($i=0; $i < $teamsCount; $i++)
    <div class="entry mb-4">
        <label class="col-form-label">{{ t('Team Title') }}</label>
        <div class="input-group">
            <div class="input-group-prepend">
                    <span class="input-group-text btn btn-primary btn-add px-3 py-0" id="basic-addon{{$i}}"><i
                                class="fas fa-plus"></i></span>
            </div>
            <input type="text" class="form-control {{ ($errors->has( a11y-form-control"teams.$i.title")) ? 'is-invalid' : '' }}"
                   id="teams[{{ $i }}][title]"
                   name="teams[{{ $i }}][title]"
                   value="{{ old("teams.$i.title", $teams[$i]->title ?? '') }}">
            <span class="invalid-feedback">{{ $errors->first("teams.$i.title") }}</span>
        </div>
        <input type="hidden" id="teams[{{ $i }}][id]" name="teams[{{ $i }}][id]"
               value="{{ old("teams.$i.id", $teams[$i]->id ?? '') }}">
    </div>
@endfor
