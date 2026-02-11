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
    <h2 class="text-center pt-4 text-uppercase">{{ t('Biospex Events') }}</h2>
    <hr class="header mx-auto" style="width:300px;">
    <div class="row">
        <div class="text-center my-4 mx-auto">
            <button class="toggle-view-btn btn btn-primary text-uppercase"
                    data-toggle="collapse"
                    data-target="#active-events-main,#completed-events-main"
                    data-value="{{ t('view active events') }}"
            >{{ t('view completed events') }}</button>
        </div>
    </div>

    <div class="row">
        <div id="active-events-main" class="col-sm-12 show">
            <livewire:front.events-index type="active" />
        </div>
        <div id="completed-events-main" class="col-sm-12 collapse">
            <canvas id="event-conffeti" style="z-index: -1; position:fixed; top:0;left:0"></canvas>
            <livewire:front.events-index type="completed" />
        </div>
    </div>
    @include('common.scoreboard')
    @include('common.event-step-chart')
@endsection

@push('scripts')
    <script src="{{ asset('js/amChartEventRate.min.js')}}"></script>
    <script>
        let eventConfetti = new ConfettiGenerator({target: 'event-conffeti'});
        eventConfetti.render();
    </script>
@endpush