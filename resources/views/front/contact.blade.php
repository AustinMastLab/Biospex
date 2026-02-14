@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Contact') }}
@stop


@section('header')
    <header id="header-img" style="background-image: url(/images/page-banners/banner-contact-smile.jpg);">
        <nav class="header navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

@section('content')
    <h1 class="page-title text-center pt-4 text-uppercase">{{ t('Biospex Contact') }}</h1>
    <hr class="header mx-auto" style="width:300px;">

    <div class="col-12 col-md-10 offset-md-1">
        <div class="jumbotron box-shadow py-5 my-5 p-sm-5">
            <div class="col-8 mx-auto">
                <form action="{{ route('front.contact.create') }}" method="post" role="form" class="recaptcha">
                    @csrf
                    @honeypot
                    <div class="form-group">
                        <label for="name" class="col-form-label required">{{ t('Name') }}:</label>
                        <input type="text"
                               class="form-control a11y-form-control {{ ($errors->has('name')) ? 'is-invalid' : '' }}"
                               id="name"
                               name="name"
                               autocomplete="name"
                               value="{{ old('name') }}"
                               required>
                        <span class="invalid-feedback" role="alert">{{ $errors->first('name') }}</span>
                    </div>
                    <div class="form-group">
                        <label for="email" class="col-form-label required">{{ t('Email') }}:</label>
                        <input type="email"
                               class="form-control a11y-form-control {{ ($errors->has('email')) ? 'is-invalid' : '' }}"
                               id="email"
                               name="email"
                               autocomplete="email"
                               inputmode="email"
                               value="{{ old('email') }}"
                               required>
                        <span class="invalid-feedback" role="alert">{{ $errors->first('email') }}</span>
                    </div>
                    <div class="form-group">
                        <label for="message" class="col-form-label required">{{ t('Message') }}:</label>
                        <textarea rows="6"
                                  class="form-control a11y-form-control {{ ($errors->has('message')) ? 'is-invalid' : '' }}"
                                  id="message"
                                  name="message"
                                  autocomplete="off"
                                  required>{{ old('message') }}</textarea>
                        <span class="invalid-feedback" role="alert">{{ $errors->first('message') }}</span>
                    </div>
                    @include('common.cancel-submit-buttons')
                </form>
            </div>
        </div>
    </div>
@endsection
