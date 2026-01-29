<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="{{ t('FSU Department of Biological Science') }}">
    <meta name="csrf-param" content="_token">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="DRVQlYZQo5OkUlUhNG8Re-CgYEB7ELA0I_3qJJlzb0U"/>
    <title>
        {{ t('BIOSPEX') }} | @yield('title')
    </title>
    @include('common.favicon')
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,700|Work+Sans:400,700" rel="stylesheet">
    <script src="https://kit.fontawesome.com/c840411e54.js" crossorigin="anonymous" SameSite="none Secure"></script>
    <link href="{{ mix('/css/front.css') }}" rel="stylesheet" type="text/css"/>
    @stack('styles')
    @production
        @include('common.google-analytics')
    @endproduction
</head>
<body>
@yield('header')
<div class="container mb-4">
    @yield('content')
    @include('common.wedigbio-progress-modal')
    @include('common.wedigbio-rate-modal')
    @if(Auth::check())
        @include('common.process-modal')
    @endif
</div>
<!--
sub footer -->
<aside style="background-color: #ededed;">
    <div class="container">
        <!-- Grid row-->
        <div class="row py-3 align-items-center">
            <!-- Grid column -->
            <div class="col-md-10 col-md-offset-1 text-center d-inline d-sm-flex align-items-start justify-content-between">
                <h3>{{ t('Get Connected') }}</h3>
                <!-- Instagram -->
                <a class="figure-img"></a>
                <!-- Twitter -->
                <a href="https://twitter.com/BIOSPEX"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="figure-img"
                       aria-label="{{ t('Twitter') }}">
                        <i class="fab fa-twitter fa-4x" aria-hidden="true"></i>
                    </a>
                <!--Facebook -->
                <a class="figure-img"></a>
                <!--LinkedIn-->
                <a class="figure-img"></a>
            </div>
        </div>
        <!-- Grid row-->
    </div>
</aside>

<!-- Footer -->
<footer class="page-footer font-small blue-grey lighten-5">
    <!-- Footer Links -->
    <div class="container text-center text-md-left">

        <!-- Grid row -->
        <div class="row mt-3 dark-grey-text align-items-center h-100">

            <!-- Grid column -->
            <div class="col-md-3 col-xl-3 mb-4">

                <!-- Content -->
                <img src="/images/page/biospex_logo.svg" alt="BIOSPEX">
                <p class="small text-justify pt-2">{{ t("is funded by a grant from the National Science Foundation's Advances in Biological Informatics Program (Award Number 1458550). iDigBio is funded by grants from the National Science Foundation's Advancing Digitization of Biodiversity Collections Program (DBI-1115210 [2011-2018] and DBI-1547229 [2016-2021]). Any opinions, findings, and conclusions or recommendations expressed in this material are those of the author(s) and do not necessarily reflect the views of the National Science Foundation.") }}</p>
            </div>
            <!-- Grid column -->
            <div class="col-sm-1">
            </div>
            <!-- Grid column -->
            <div class="col-md-2 col-12 mx-auto mb-4">
                <!-- Links -->
                <h6 class="text-uppercase font-weight-bold">{{ t('About') }}</h6>
                <hr class="white mb-2 mt-2 d-inline-block mx-auto" style="width:60px;">
                <p>
                    <a href="{{ route('front.teams.index') }}">{{ t('Team') }}</a>
                </p>
                <p>
                    <a href="{{ route('front.faqs.index') }}">{{ t('FAQ') }}</a>
                </p>
                <p>
                    <a href="{{ route('front.site-assets.index') }}">{{ t('Resources') }}</a>
                </p>
                <p>
                    <a href="{{ route('front.contact.index') }}">{{ t('Contact') }}</a>
                </p>

            </div>
            <!-- Grid column -->
            <!-- Grid column -->
            <div class="col-md-2  mx-auto mb-4">
                <h6 class="text-uppercase font-weight-bold">{{ t('Content') }}</h6>
                <hr class="white mb-2 mt-2 d-inline-block mx-auto" style="width:60px;">
                <p>
                    <a href="{{ route('front.projects.index') }}">{{ t('Projects') }}</a>
                </p>
                <p>
                    <a href="{{ route('front.expeditions.index') }}">{{ t('Expeditions') }}</a>
                </p>
                <p>
                    <a href="{{ route('front.events.index') }}">{{ t('Events') }}</a>
                </p>
                <p>
                    <a href="{{ route('api.index.get') }}" class="text-uppercase">{{ t('Biospex API') }}</a>
                </p>
            </div>
            <!-- Grid column -->
        </div>
        <!-- Grid row -->

    </div>
    <!-- Footer Links -->

    <!-- Copyright -->
    <div class="text-center py-3" style="color: #e1e1e1;">{{ t('© 2014–%s Copyright', \Carbon\Carbon::now()->year) }}
        <a href="https://www.bio.fsu.edu/"> {{ t('FSU Department of Biological Science') }}</a>
    </div>
    <!-- Copyright -->

</footer>
@if(\Route::current()->getName() !== 'home')
    @include('front.layout.contributors')
@endif
@include('common.php-vars-javascript')
<script src="{{ mix('/js/manifest.js') }}"></script>
<script src="{{ mix('/js/vendor.js') }}"></script>
<script src="{{ mix('/js/front.js') }}"></script>
@include('common.amchart')

@stack('scripts')
</body>
</html>