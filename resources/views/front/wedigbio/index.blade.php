@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('WeDigBio Events') }}
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
    <h1 class="page-title text-center pt-4 text-uppercase">{{ t('WeDigBio Events') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <div class="row">
        <div class="col-sm-12 mt-5">
            <canvas id="event-conffeti" style="z-index: -1; position:fixed; top:0;left:0"></canvas>
            <div id="active-events" class="row col-sm-12 mx-auto justify-content-center">
                @each('front.wedigbio.partials.event', $events, 'event')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let eventConfetti = new ConfettiGenerator({target: 'event-conffeti'});
        eventConfetti.render();
    </script>
@endpush