@for($i=0; $i < 24; $i++)
    <div class="row">
        <div class="col-sm-12">
            <div class="input-group">
                <div class="col-4 mb-2">
                    <label for="word" class="col-form-label required">{{ t('Word') }}:</label>
                    <input type="text"
                           class="form-control {{ ($errors->has( a11y-form-control"words.$i.word")) ? 'is-invalid' : '' }}"
                           id="words[{{ $i }}][word]"
                           pattern=".{1,30}" title="1 to 30 characters"
                           name="words[{{ $i }}][word]"
                           value="{{ old("words.$i.word", $words[$i]->word ?? '') }}"
                           placeholder="Max 30 characters" required>
                    <span class="invalid-feedback">{{ $errors->first("words.$i.word") }}</span>
                    <input type="hidden" id="words[{{ $i }}][id]" name="words[{{ $i }}][id]"
                           value="{{ old("words.$i.id", $words[$i]->id ?? '') }}">
                </div>
                <div class="col-8 mb-2">
                    <label for="definition" class="col-form-label">{{ t('Mouseover Text') }}:</label>
                    <input type="text"
                           class="form-control a11y-form-control"
                           id="words[{{ $i }}][definition]"
                           pattern=".{1,200}" title="1 to 200 characters"
                           name="words[{{ $i }}][definition]"
                           value="{{ old("words.$i.definition", $words[$i]->definition ?? '') }}"
                           placeholder="Mouseover text for word, max 200 characters">
                </div>
            </div>
        </div>
    </div>
@endfor
