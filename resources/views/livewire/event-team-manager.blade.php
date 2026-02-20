<div class="controls">
    @foreach($teams as $index => $team)
        @php($teamTitleId = 'team_title_' . $index)

        <div class="entry mb-4" wire:key="team-{{ $index }}">
            <label class="col-form-label" for="{{ $teamTitleId }}">{{ t('Team Title') }}</label>

            <div class="input-group">
                <div class="input-group-prepend">
                    <button
                            type="button"
                            class="input-group-text btn btn-primary px-3 py-0"
                            wire:click="addTeam"
                            style="cursor: pointer;"
                            aria-label="{{ t('Add team') }}"
                    >
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        <span class="sr-only">{{ t('Add team') }}</span>
                    </button>
                </div>

                <input
                        type="text"
                        class="form-control a11y-form-control {{ ($errors && (is_array($errors) ? isset($errors["teams.$index.title"]) : $errors->has("teams.$index.title"))) ? 'is-invalid' : '' }}"
                        id="{{ $teamTitleId }}"
                        name="teams[{{ $index }}][title]"
                        wire:model.defer="teams.{{ $index }}.title"
                        value="{{ old("teams.$index.title", $team['title'] ?? '') }}"
                        placeholder="{{ t('Team Title') }}"
                        required
                >

                @if(count($teams) > 1)
                    <div class="input-group-append">
                        <button
                                type="button"
                                class="input-group-text btn btn-danger px-3 py-0"
                                wire:click="removeTeam({{ $index }})"
                                style="cursor: pointer;"
                                aria-label="{{ t('Remove team') }}"
                        >
                            <i class="fas fa-minus" aria-hidden="true"></i>
                            <span class="sr-only">{{ t('Remove team') }}</span>
                        </button>
                    </div>
                @endif

                <span class="invalid-feedback">
                    {{ ($errors && (is_array($errors) ? isset($errors["teams.$index.title"]) : $errors->has("teams.$index.title"))) ? (is_array($errors) ? $errors["teams.$index.title"][0] : $errors->first("teams.$index.title")) : '' }}
                </span>
            </div>

            @if(isset($team['id']) && $team['id'])
                <input
                        type="hidden"
                        name="teams[{{ $index }}][id]"
                        wire:model.defer="teams.{{ $index }}.id"
                        value="{{ $team['id'] }}"
                >
            @endif
        </div>
    @endforeach
</div>