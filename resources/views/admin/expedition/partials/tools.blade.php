@use('App\Enums\ActorExpeditionState')
<div class="col-md-12 text-center">
    <div class="btn-group-lg btn-group-vertical mb-2 align-items-center">
        @if($expedition->project->ocrQueue->isEmpty())
            <h4>{{ t('OCR') }}</h4>
            {!! $expedition->present()->expedition_ocr_btn !!}
        @endif
        @php($complete = false)
        @foreach ($expedition->actors as $actor)
            @if((int)$actor->id === (int)config('zooniverse.actor_id'))
                @php($complete = $actor->pivot->state === ActorExpeditionState::Complete->value)
                @include('admin.expedition.partials.zooniverse-btns')
            @endif
            @if((int)$actor->id === (int)config('geolocate.actor_id') && $complete)
                @include('admin.expedition.partials.geolocate-btns')
            @endif
        @endforeach
    </div>
</div>
