<div class="controls">
    @foreach($teams as $index => $team)
        <div class="entry mb-4" wire:key="team-{{ $index }}">
            <label class="col-form-label">{{ t('Team Title') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text btn btn-primary px-3 py-0"
                          wire:click="addTeam"
                          style="cursor: pointer;"
                          id="basic-addon{{$index}}">
                        <i class="fas fa-plus"></i>
                    </span>
                </div>
                <input type="text"
                       class="form-control {{ ($errors && (is_array($errors) ? isset($errors[ a11y-form-control"teams.$index.title"]) : $errors->has("teams.$index.title"))) ? 'is-invalid' : '' }}"
                       id="teams[{{ $index }}][title]" 
                       name="teams[{{ $index }}][title]"
                       wire:model.defer="teams.{{ $index }}.title"
                       value="{{ old("teams.$index.title", $team['title'] ?? '') }}"
                       placeholder="{{ t('Team Title') }}" required>
                @if(count($teams) > 1)
                    <div class="input-group-append">
                        <span class="input-group-text btn btn-danger px-3 py-0"
                              wire:click="removeTeam({{ $index }})"
                              style="cursor: pointer;">
                            <i class="fas fa-minus"></i>
                        </span>
                    </div>
                @endif
                <span class="invalid-feedback">{{ ($errors && (is_array($errors) ? isset($errors["teams.$index.title"]) : $errors->has("teams.$index.title"))) ? (is_array($errors) ? $errors["teams.$index.title"][0] : $errors->first("teams.$index.title")) : '' }}</span>
            </div>
            
            <!-- Hidden input for team id if it exists -->
            @if(isset($team['id']) && $team['id'])
                <input type="hidden"
                       name="teams[{{ $index }}][id]"
                       wire:model.defer="teams.{{ $index }}.id"
                       value="{{ $team['id'] }}">
            @endif
        </div>
    @endforeach
</div>