<div class="col-md-4 mb-4">
    <div class="card px-4 box-shadow h-100">
        <div class="card-body text-center">
            @if(event_before($event, 'UTC'))
                <p class="card-text event-card-status">{{ t('Starting') }}</p>
                <div class="clockdiv" data-value="{{ $event->present()->progress_date }}">
                    <div>
                        <span class="days"></span>
                        <div class="smalltext">{{ t('Days') }}</div>
                    </div>
                    <div>
                        <span class="hours"></span>
                        <div class="smalltext">{{ t('Hours') }}</div>
                    </div>
                    <div>
                        <span class="minutes"></span>
                        <div class="smalltext">{{ t('Minutes') }}</div>
                    </div>
                    <div>
                        <span class="seconds"></span>
                        <div class="smalltext">{{ t('Seconds') }}</div>
                    </div>
                </div>
            @elseif(event_active($event, 'UTC'))
                <p class="card-text event-card-status">{{ t('Happening Now!') }}</p>
            @else
                <p class="card-text event-card-status event-card-status-completed">{{ t('Completed') }}</p>
            @endif
            <h3 class="text-center pt-4 event-card-title">{{ t('WeDigBio') }}</h3>
            <p class="text-center color-action event-card-meta">
                {{ $event->present()->start_date_to_string }} {{ t('To') }}<br>
                {{ $event->present()->end_date_to_string }} {{ t('UTC') }}<br>
            </p>
        </div>
        @if(! event_before($event, 'UTC'))
            <div class="text-center">
                <button class="btn btn-primary mb-4 text-uppercase"
                        data-toggle="modal"
                        data-remote="false"
                        data-target="#wedigbio-progress-modal"
                        data-href="{{ route('front.wedigbio-progress', [$event]) }}"
                        data-channel="{{ config('config.poll_wedigbio_progress_channel') . '.' . $event->channel_key }}"
                        data-uuid="{{ $event->channel_key }}">{{ t('Progress') }}
                </button>

                <button class="btn btn-primary mb-4 text-uppercase"
                        data-toggle="modal"
                        data-remote="false"
                        data-target="#wedigbio-rate-modal"
                        data-projects="{{ route('front.get.wedigbio-projects', [$event]) }}"
                        data-uuid="{{ $event->channel_key }}"
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
