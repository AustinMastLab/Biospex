@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Register') }}
@stop

{{-- Content --}}
@section('header')
    <header style="background-image: url(/images/page-banners/banner-field.jpg);">
        <nav class="header navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

@section('content')
    <h2 class="text-center pt-4 text-uppercase">{{ t('Register Account') }}</h2>
    <hr class="header mx-auto" style="width:300px;">
    <div class="col-12 col-md-10 offset-md-1">
        <div class="card white box-shadow py-5 my-5 p-sm-5">
            <div class="col-6 mx-auto">
                <form action="{{ route('app.post.register', ['invite' => $invite]) }}" method="post" role="form"
                      class="form-horizontal">
                    @csrf
                    @honeypot
                    <div class="form-group">
                        <label for="name" class="col-form-label required">{{ t('Display Name') }}:</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror a11y-form-control"
                               id="name" name="name"
                               value="{{ old('name') }}" required>
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="email" class="col-form-label required">{{ t('Email') }}:</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror a11y-form-control"
                               id="email" name="email"
                               value="{{ old('email', isset($invite->email) ? $invite->email : null) }}" required>
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="password" class="col-form-label required">{{ t('Password') }}:</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror a11y-form-control"
                               id="password" name="password"
                               value="{{ old('password') }}" required>
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation" class="col-form-label required">{{ t('Confirm Password') }}
                            :</label>
                        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror a11y-form-control"
                               id="password_confirmation" name="password_confirmation"
                               value="{{ old('password_confirmation') }}" required>
                        @error('password_confirmation')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    @include('common.submit-button')
                </form>
                <div class="mt-4 text-center">
                    <a href="{{ route('app.get.login') }}">{{ t('Already have an account? Login') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
