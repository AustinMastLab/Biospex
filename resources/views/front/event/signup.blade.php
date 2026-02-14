@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Event Team Registration') }}
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
    <h2 class="text-center pt-4 text-uppercase">
        {{ t('Biospex Event Team Registration') }}
    </h2>
    <hr class="header mx-auto" style="width:300px;">

    <div class="col-12 col-md-10 offset-md-1">
        <div class="jumbotron box-shadow py-5 my-5 p-sm-5">
            <div class="col-8 mx-auto">
                <div class="row">
                    <div class="col-md-10 col-md-offset-2">
                        <h4>{{ t('Event') }}: <small>{{ $team->event->title }}</small></h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-10 col-md-offset-2">
                        <h4>{{ t('Team') }}: <small>{{ $team->title }}</small></h4>
                    </div>
                </div>
                <div class="row">
                    @if ($errors->any())
                        @foreach($errors->all() as $error)
                            <div class="red">{{$error}}</div>
                        @endforeach
                    @endif
                </div>
                <form action="{{ route('front.events_team_user.store', [$team]) }}" method="post" role="form"
                      class="recaptcha">
                    @csrf
                    <div class="form-group {{ ($errors->has('nfn_user')) ? 'has-error' : '' }}">
                        <label for="name" class="col-form-label required">{{ t('Zooniverse Username') }}:</label>
                        @if($active)
                            <input type="text" class="form-control {{ ($errors->has('nfn_user')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="nfn_user" name="nfn_user" value="{{ old('nfn_user') }}" required>
                        @else
                            <input type="text" class="form-control {{ ($errors->has('nfn_user')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="nfn_user" name="nfn_user" value="" placeholder="{{ t('Event Closed') }}"
                                   disabled="disabled">
                        @endif
                        <span class="invalid-feedback">{{ $errors->first('nfn_user') }}</span>
                    </div>
                    @include('common.recaptcha')
                    @include('common.submit-button')
                </form>
            </div>
        </div>
    </div>
@stop