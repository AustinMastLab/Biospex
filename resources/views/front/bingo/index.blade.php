@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Bingo') }}
@stop

@section('header')
    <header id="header-img" style="background-image: url(/images/page-banners/banner-games.jpg);">
        <nav class="header navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

{{-- Content --}}
@section('content')
    <h1 class="page-title text-center text-uppercase pt-4">{{ t('Biospex Bingo') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <div class="row">
        @if($bingos->isNotEmpty())
            @foreach($bingos as $bingo)
                @include('front.bingo.partials.bingo-loop', ['project' => $bingo->project])
            @endforeach
        @else
            <h2 class="mx-auto pt-4">{{ t('No Bingo Games exist.') }}</h2>
        @endif
    </div>
@endsection