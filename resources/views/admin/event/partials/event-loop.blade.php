<div class="col-md-4 mb-4">
    <div class="card px-4 box-shadow h-100">
        <div class="card-body text-center">
            @if(event_before($event))
                <p class="card-text event-card-status">{{ t('Starting') }} {{ $event->present()->start_date_to_string }}</p>
            @elseif( ! event_active($event))
                <p class="card-text event-card-status event-card-status-completed">{{ t('Completed') }}</p>
            @else
                <p class="card-text event-card-status">{{ t('Time Remaining') }}</p>
                <div class="clockdiv" data-value="{{ $event->present()->scoreboard_date }}">
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
            @endif
            <h3 class="text-center pt-4 event-card-title">{{ $event->title }}</h3>
            <p class="text-center color-action event-card-meta">
                {{ $event->present()->start_date_to_string }}<br>
                {{ t('to') }}<br>
                {{ $event->present()->end_date_to_string }}<br>
                {{ str_replace('_', ' ', $event->timezone) }}<br>
                {{ t('for') }} {{ $event->project->title }}
            </p>
        </div>
        @if( ! event_before($event))
            <div class="text-center">
                <button class="btn btn-primary mb-4 text-uppercase" data-toggle="modal"
                        data-remote="false"
                        data-target="#scoreboard-modal"
                        data-channel="{{ config('config.poll_scoreboard_channel') .'.'. $event->project_id }}"
                        data-event="{{ $event->uuid }}"
                        data-href="{{ route('event.get.scoreboard', [$event]) }}">{{ t('Scoreboard') }}
                </button>
                @if($event->teams->isNotEmpty())
                    <button class="btn btn-primary mb-4 text-uppercase" data-toggle="modal"
                            data-remote="false"
                            data-target="#step-chart-modal"
                            data-teams="{{ $event->teams->pluck('title')->implode(',') }}"
                            data-timezone="{{ event_rate_chart_timezone(($event->timezone)) }}"
                            data-href="{{ route('event.get.rate', [$event]) }}">{{ t('Rate Chart') }}
                    </button>
                @endif
            </div>
        @endif
        <div class="card-footer">
            <div class="d-flex align-items-start justify-content-between mt-4 mb-3">
                {!! $event->project->present()->project_page_icon !!}
                {!! $event->present()->event_admin_show_icon !!}
                @if(event_before($event) || event_active($event))
                    @if($event->project->lastPanoptesProject)
                        {!! $event->project->lastPanoptesProject->present()->project_icon !!}
                    @endif
                @endif
                {!! $event->present()->event_edit_icon !!}
                @can('isOwner', $event)
                    {!! $event->present()->event_delete_icon !!}
                @endcan
            </div>
        </div>
    </div>
</div>
