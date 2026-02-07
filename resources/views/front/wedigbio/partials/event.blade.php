<div class="col-md-4 mb-4">
    <div class="card px-4 box-shadow h-100">
        <div class="card-body text-center">
            @if(event_before($event, 'UTC'))
                <h3 class="card-text">{{ t('Starting') }} {{ $event->present()->start_date_to_string }}</h3>
            @endif
            @if($event->active)
                <h3 class="card-text">{{ t('Happening Now!') }}</h3>
            @endif
            <h4 class="text-center pt-4">{{ t('WeDigBio') }}</h4>
            <h5 class="text-center color-action">
                {{ $event->present()->start_date_to_string }} {{ t('To') }}<br>
                {{ $event->present()->end_date_to_string }} {{ t('UTC') }}<br>
            </h5>
        </div>
        @if( ! event_before($event, 'UTC'))
            <div class="text-center">
                <button class="btn btn-primary mb-4 text-uppercase"
                        data-toggle="modal"
                        data-remote="false"
                        data-target="#wedigbio-progress-modal"
                        data-href="{{ route('front.wedigbio-progress', [$event]) }}"
                        data-channel="{{ config('config.poll_wedigbio_progress_channel') . '.' . $event->uuid }}"
                        data-uuid="{{ $event->uuid }}">{{ t('Progress') }}
                </button>

                <button class="btn btn-primary mb-4 text-uppercase"
                        data-toggle="modal"
                        data-remote="false"
                        data-target="#wedigbio-rate-modal"
                        data-projects="{{ route('front.get.wedigbio-projects', [$event]) }}"
                        data-uuid="{{ $event->uuid }}"
                        data-href="{{ route('front.get.wedigbio-rate', [$event]) }}">{{ t('Rates') }}
                </button>
            </div>
        @endif
        <!--
        <div class="card-footer">
            <div class="d-flex align-items-start justify-content-between mt-4 mb-3">
            </div>
        </div>
        -->
    </div>
</div>
