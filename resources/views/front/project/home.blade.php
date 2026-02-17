@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ $project->title }}
@stop

@section('header')
    <header style="background-image: url({{ $project->present()->banner_file_url }});">
        <nav class="header navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

{{-- Content --}}
@section('content')
    <div class="row">
        <div class="col-sm-10 mx-auto">
            <div class="jumbotron box-shadow pt-2 pb-5 my-5 p-sm-5">
                <h1 class="text-center project-wide text-uppercase">
                    <small style="font-size:16px;">{{ t('Featured BIOSPEX Project') }}</small>
                    <br>{{ $project->title }}</h1>
                <div class="col-12">
                    <div class="d-flex align-items-start justify-content-between mt-4 mb-3">
                        {!! $project->present()->project_expeditions_icon_lrg !!}
                        {!! $project->present()->project_events_icon_lrg !!}
                        {!! $project->present()->twitter_icon_lrg !!}
                        {!! $project->present()->facebook_icon_lrg !!}
                        {!! $project->present()->blog_icon_lrg !!}
                        {!! $project->present()->contact_email_icon_lrg !!}
                    </div>
                </div>

                <hr class="pt-0 pb-4">

                <div class="col-12 col-md-10 offset-md-1">
                    <div class="col-5 float-right">
                        <img src="{{ $project->present()->show_logo }}" class="img-fluid"
                             alt="{{ $project->title }} logo">
                    </div>
                    @if($project->contact !== null)
                        <h3>{{ t('Contact') }}</h3>
                        <p>
                            <a href="mailto:{{ $project->contact_email }}" class="text">{{ $project->contact }}</a>
                        </p>
                    @endif

                    @if($project->organization !== null)
                        <h3>{{ t('Organization') }}</h3>
                        @if($project->organization_website !== null)
                            <p><a href="{{ $project->organization_website }}"
                                  target="_blank">{{ $project->organization }}</a></p>
                        @else
                            <p>{{ $project->organization }}</p>
                        @endif
                    @endif

                    @if($project->project_partners !== null)
                        <h3>{{ t('Partners') }}</h3>
                        <p>{{ $project->project_partners }}</p>
                    @endif

                    @if($project->funding_source !== null)
                        <h3>{{ t('Funding Source') }}</h3>
                        <p>{{ $project->funding_source  }}</p>
                    @endif

                    @if($project->description_long !== null)
                        <h3>{{ t('Description') }}</h3>
                        @if($project->description_short !== null)
                            <p><strong>{{ $project->description_short  }}</strong></p>
                        @endif
                        <p>{!! $project->description_long !!}</p>
                    @endif

                    @if($project->incentives !== null)
                        <h3>{{ t('Incentives') }}</h3>
                        <p>{{ $project->incentives }}</p>
                    @endif

                    @if($project->geographic_scope !== null)
                        <h3>{{ t('Geographic Scope') }}</h3>
                        <p>{{ $project->geographic_scope }}</p>
                    @endif

                    @if($project->taxonomic_scope !== null)
                        <h3>{{ t('Taxonomic Scope') }}</h3>
                        <p>{{ $project->taxonomic_scope }}</p>
                    @endif

                    @if($project->temporal_scope !== null)
                        <h3>{{ t('Temporal Scope') }}</h3>
                        <p>{{ $project->temporal_scope }}</p>
                    @endif

                    @if($project->language_skills !== null)
                        <h3>{{ t('Language Skills Required') }}</h3>
                        <p>{{ $project->language_skills }}</p>
                    @endif

                    @if($project->activities !== null)
                        <h3>{{ t('Activities') }}</h3>
                        <p>{{ $project->activities }}</p>
                    @endif

                    @if($project->assets->isNotEmpty())
                        <h3>{{ t('Resources') }}</h3>
                        @foreach($project->assets as $asset)
                            <p>{!! $asset->present()->asset !!}</p>
                        @endforeach
                    @endif

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-8 offset-md-2 mt-5">
            <h1 class="text-center content-header text-uppercase mt-5" id="expeditions">{{ t('Expeditions') }}</h1>
            <div class="text-center mt-4">
                <button class="toggle-view-btn btn btn-primary text-uppercase"
                        data-toggle="collapse"
                        data-target="#active-expeditions-main,#completed-expeditions-main"
                        data-value="{{ t('view active expeditions') }}"
                >{{ t('view completed expeditions') }}</button>
            </div>
            <div class="d-flex align-items-start justify-content-between mt-4 mb-3">
                <span class="text">{{ $project->expeditions_count }} {{ t('Expeditions') }}</span>
                <span class="text">{{ $project->expedition_stats_sum_transcriptions_completed }} {{ t('Digitizations') }}</span>
                <span class="text">{{ get_project_transcriber_count($project->id) }} {{ t('Participants') }}</span>
            </div>
            <hr class="header mx-auto">
        </div>
        <div id="active-expeditions-main" class="col-sm-12 show">
            <livewire:front.expeditions-index type="active" :project-id="$project->id" />
        </div>
        <div id="completed-expeditions-main" class="col-sm-12 collapse">
            <canvas id="expedition-conffeti" style="z-index: -1; position:fixed; top:0;left:0;"></canvas>
            <livewire:front.expeditions-index type="completed" :project-id="$project->id" />
        </div>
    </div>

    <div class="row">
        <div class="col-sm-8 offset-md-2 mt-5">
            <h1 class="text-center content-header mt-5" id="events">{{ t('Events') }}</h1>
            <div class="text-center mt-4">
                <button class="toggle-view-btn btn btn-primary text-uppercase"
                        data-toggle="collapse"
                        data-target="#active-events-main,#completed-events-main"
                        data-value="{{ t('view active events') }}"
                >{{ t('view completed events') }}</button>
            </div>
            <hr class="header mx-auto">
        </div>
        <div id="active-events-main" class="col-sm-12 show">
            <livewire:front.events-index type="active" :project-id="$project->id" />
        </div>
        <div id="completed-events-main" class="col-sm-12 collapse">
            <canvas id="event-conffeti" style="z-index: -1; position:fixed; top:0;left:0"></canvas>
            <livewire:front.events-index type="completed" :project-id="$project->id" />
        </div>
        @include('common.scoreboard')
        @include('common.event-step-chart')
    </div>

    @if($project->bingos->isNotEmpty())
        <div class="row">
            <div class="col-sm-8 offset-md-2 mt-5">
                <h1 class="text-center content-header mt-5">{{ t('Games') }}</h1>
                <hr class="header mx-auto">
            </div>
            <div id="games-main" class="col-sm-12 show">
                <div id="games" class="row col-sm-12 mx-auto justify-content-center">
                    @foreach($project->bingos as $bingo)
                        @include('front.bingo.partials.bingo-loop', ['project' => $project])
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($years !== null)
        <div class="row">
            <div class="col-sm-10 mx-auto mt-5">
                <h1 class="text-center content-header text-uppercase mt-5"
                    id="digitizations">{{ t('Digitizations') }}</h1>
                <div class="text-center mt-4 mb-4">
                    @foreach($years as $year)
                        <button class="btn btn-primary btn-transcription" id="year{{ $year }}"
                                data-href="{{ route('front.projects.transcriptions', [$project, $year]) }}">{{ $year }}
                        </button>
                    @endforeach
                </div>
                <hr class="header mx-auto">
                <div class="jumbotron box-shadow pt-2 pb-5">
                    <div id="transcripts"
                         style="color: #000000; font-size: 0.8em"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-10 mx-auto mt-5">
                <h1 class="text-center content-header text-uppercase mt-5"
                    id="specimens">{{ t('Heat Map Digitized Specimens') }}</h1>
                <hr class="header mx-auto">
                <div class="jumbotron box-shadow pt-2 pb-5">
                    <div id="mapDiv" class="d-flex" style="width:100%; height: 500px"></div>
                    <div id="mapLegendDiv" style="width:100%; height: 100px"></div>
                    <div class="hide" id="projectUrl"
                         data-href="{{ route('front.projects.state', [$project]) }}"></div>
                </div>
            </div>
        </div>
        @include('common.script-modal')
    @endif

@endsection
@push('scripts')
    @if ($years !== null)
        <script src="{{ asset('js/amChartTranscript.min.js')}}"></script>
        <script src="{{ asset('js/amChartMap.min.js')}}"></script>
    @endif
    <script src="{{ asset('js/amChartEventRate.min.js')}}"></script>
    <script>
        let expeditionConfetti = new ConfettiGenerator({target: 'expedition-conffeti'});
        expeditionConfetti.render();

        let eventConfetti = new ConfettiGenerator({target: 'event-conffeti'});
        eventConfetti.render();
    </script>
@endpush

