@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Expeditions') }}
@stop

@section('header')
    <header id="header-img" style="background-image: url(/images/page-banners/banner-image-girl.jpg);">
        <nav class="header navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

{{-- Content --}}
@section('content')
    <h2 class="text-center pt-4 text-uppercase">{{ t('Biospex Expeditions') }}</h2>
    <hr class="header mx-auto" style="width:300px;">
    <div class="row">
        <div class="text-center mx-auto my-4">
            <button class="toggle-view-btn btn btn-primary pl-4 pr-4 text-uppercase"
                    data-toggle="collapse"
                    data-target="#active-expeditions-main,#completed-expeditions-main"
                    data-value="{{ t('view active expeditions') }}"
            >{{ t('view completed expeditions') }}</button>
        </div>
    </div>
    <div class="row">
        <div id="active-expeditions-main" class="col-sm-12 show">
            <livewire:front.expeditions-index type="active" />
        </div>
        <div id="completed-expeditions-main" class="col-sm-12 collapse">
            <canvas id="expedition-conffeti" style="z-index: -1; position:fixed; top:0;left:0;"></canvas>
            <livewire:front.expeditions-index type="completed" />
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let expeditionConfetti = new ConfettiGenerator({target: 'expedition-conffeti'});
        expeditionConfetti.render();
    </script>
@endpush
