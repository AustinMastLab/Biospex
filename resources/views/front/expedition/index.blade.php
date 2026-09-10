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
    <h1 class="page-title text-center pt-4 text-uppercase">{{ t('Biospex Expeditions') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <h2 class="sr-only">{{ t('Expeditions list') }}</h2>
    <canvas id="expedition-conffeti" style="z-index: -1; position:fixed; top:0;left:0; display: none;"></canvas>
    <livewire:front.expeditions-index />
@endsection

@push('scripts')
    <script>
        let expeditionConfetti;
        document.addEventListener('livewire:init', () => {
            Livewire.on('expedition-type-changed', ({type}) => {
                let canvas = document.getElementById('expedition-conffeti');

                if (type === 'completed') {
                    canvas.style.display = 'block';
                    expeditionConfetti = new ConfettiGenerator({target: canvas});
                    expeditionConfetti.render();

                    return;
                }

                expeditionConfetti?.clear();
                canvas.style.display = 'none';
            });
        });
    </script>
@endpush
