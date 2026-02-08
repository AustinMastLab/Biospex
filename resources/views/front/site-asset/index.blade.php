@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Resources') }}
@stop

@section('header')
    <header id="header-img" style="background-image: url(/images/page-banners/banner-seaoates.jpg);">
        <nav class="header navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

@section('content')
    <h1 class="page-title text-center pt-4 text-uppercase">{{ t('Biospex Resources') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <div class="row col-sm-12 mx-auto mt-4 justify-content-center">
        @include('front.site-asset.partials.asset')
    </div>
@endsection

@section('footer')
    @include('front.layout.contributors')
@endsection