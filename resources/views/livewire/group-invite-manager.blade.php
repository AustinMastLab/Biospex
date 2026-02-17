<div class="controls">
    @foreach($invites as $index => $invite)
        <div class="entry mb-4" wire:key="invite-{{ $index }}">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text btn btn-primary px-3 py-0"
                          wire:click="addInvite"
                          style="cursor: pointer;"
                          id="basic-addon{{$index}}">
                        <i class="fas fa-plus"></i>
                    </span>
                </div>

                <label class="sr-only" for="invites-{{ $index }}-email">
                    {{ t('Email address') }}
                </label>
                <input type="email"
                       class="form-control {{ ($errors && isset($errors["invites.$index.email"])) ? 'is-invalid' : '' }} a11y-form-control"
                       id="invites-{{ $index }}-email"
                       name="invites[{{ $index }}][email]"
                       autocomplete="email"
                       wire:model.defer="invites.{{ $index }}.email"
                       value="{{ old("invites.$index.email", $invite['email'] ?? '') }}"
                       placeholder="{{ t('Email') }}" required>

                @if(count($invites) > 1)
                    <div class="input-group-append">
                        <span class="input-group-text btn btn-danger px-3 py-0"
                              wire:click="removeInvite({{ $index }})"
                              style="cursor: pointer;">
                            <i class="fas fa-minus"></i>
                        </span>
                    </div>
                @endif

                <span class="invalid-feedback">{{ ($errors && isset($errors["invites.$index.email"])) ? $errors["invites.$index.email"][0] : '' }}</span>
            </div>

            <!-- Hidden input for invite id if it exists -->
            @if(isset($invite['id']) && $invite['id'])
                <input type="hidden"
                       name="invites[{{ $index }}][id]"
                       wire:model.defer="invites.{{ $index }}.id"
                       value="{{ $invite['id'] }}">
            @endif
        </div>
    @endforeach
</div>