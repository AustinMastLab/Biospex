@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Team') }}
@stop

{{-- Content --}}
@section('header')
    <header id="header-img" style="background-image: url(/images/page-banners/banner-diversity.jpg);">
        <nav class="header navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

@section('content')
    <h1 class="page-title text-center pt-4 text-uppercase">{{ t('Biospex Team') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    @foreach($categories as $category)
        <div class="row col-sm-10 mx-auto mt-4 justify-content-center">
            @include('front.team.partials.categories')
        </div>
    @endforeach
@endsection
