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
    <h1 class="page-title text-center pt-4 text-uppercase">{{ t('Biospex Events') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <h2 class="sr-only">{{ t('Events list') }}</h2>
    <canvas id="event-conffeti" style="z-index: -1; position:fixed; top:0;left:0; display: none;"></canvas>
    <livewire:front.events-index />
    @include('common.scoreboard')
    @include('common.event-step-chart')
@endsection

@push('scripts')
    <script src="{{ asset('js/amChartEventRate.min.js')}}"></script>
    <script>
        let eventConfetti;
        document.addEventListener('livewire:init', () => {
            Livewire.on('event-type-changed', ({type}) => {
                let canvas = document.getElementById('event-conffeti');

                if (type === 'completed') {
                    canvas.style.display = 'block';
                    eventConfetti = new ConfettiGenerator({target: canvas});
                    eventConfetti.render();

                    return;
                }

                eventConfetti?.clear();
                canvas.style.display = 'none';
            });
        });
    </script>
@endpush