@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Events') }}
@stop

@section('header')
    <header id="header-img" style="background-image: url(/images/page-banners/banner-image-group.jpg);">
        <nav class="header navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

{{-- Content --}}
@section('content')
    <h1 class="page-title text-center pt-4 text-uppercase">{{ $event->title }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <div class="d-flex align-items-center justify-content-center ">
        @include('front.event.partials.event-loop')
    </div>
    @if(event_before($event) || event_active($event))
        <div class="row">
            <p class="text-center col-6 mx-auto mt-4">{!! $event->project->lastPanoptesProject->present()->project_link !!}</p>
        </div>
        <div class="row">
            <p class="text-justify col-6 mx-auto mt-4"><b>{{ t('Important') }}: </b>
                {{ t('Transcriptions will only count during the scheduled Event times. Please be sure to log in to Zooniverse when digitizing or your digitization will not count. To participate on a Team, please contact the Event sponsor.') }}
            </p>
        </div>
    @endif
    @include('common.scoreboard')
    @include('common.event-step-chart')
@endsection

@push('scripts')
    @if( ! event_before($event))
        <script src="{{ asset('js/amChartEventRate.min.js')}}"></script>
    @endif
@endpush

