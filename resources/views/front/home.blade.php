@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
{{ t('Home') }}
@stop

@push('styles')
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
@endpush

{{-- Content --}}
@section('header')
    <header class="header home">
        <nav class="navbar navbar-expand-md">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
        <div class="container text-center">
            <div class="row py-5">
                <div class="col-12" data-aos="fade-right" data-aos-easing="ease-in" data-aos-duration="2000">
                    <h1 class="text-white align-middle home-banner-tag">{{ t('Provision, advertise, and lead expeditions.') }}
                        <br>
                        <a href="#learn-more" data-scroll class="btn btn-primary mt-4" data-aos="fade-right"
                           data-aos-easing="ease-out" data-aos-duration="3000"
                           aria-label="{{ t('Learn more about BIOSPEX') }}">{{ t('Learn More') }}</a>
                    </h1>

                </div>
            </div>
        </div>
    </header>
    @include('front.layout.contributors')
@endsection

@section('content')
    <section class="home-heading text-center" id="learn-more">
        <div class="container">
            <img src="/images/page/logo-tagline-action.png" alt="BIOSPEX: Lead Public Digitization Expeditions">
            <p class="text-justify mt-4">{{ t('BIOSPEX is a base camp for launching, advertising, and managing targeted
            efforts to digitize the world\'s 3 billion biodiversity research specimens in ways that involve the public.
            Such specimens include fish in jars, plants on sheets, fossils in drawers, insects on pins, and many other
            types. “Digitization” is a broad reference to creating digital data about the physical specimens and includes
            things like recording the what, when, where from the specimen label or describing the life stage of the specimen
            at time of collection. BIOSPEX enables you to package projects in one or a series of digitization expeditions,
            launch the expeditions at crowdsourcing tools, widely recruit others to participate, and layer resources on
            the experience to advance science literacy. In the end, you can download the new data for specimen curation,
            research, conservation, natural resource management, public policy, or other activities.') }}</p>
        </div>
    </section>
    <!-- Carousel Section -->
    <section>
        <div class="row">
            <div class="col d-flex justify-content-center">
                <div class="col-md-4 text-center" role="group" aria-label="External carousel buttons">
                    <h2>The Process<br>
                        <span class="smallertext">{{ t('Specimen digitization is easy as 123') }}</span></h2>
                    <ul id="external-carousel-btns" class="list-inline">
                        <li data-target="#processCarousel" data-slide-to="0"
                            class="carousel-li-0 active list-inline-item">1
                        </li>
                        <li data-target="#processCarousel" data-slide-to="1" class="carousel-li-1 list-inline-item">2
                        </li>
                        <li data-target="#processCarousel" data-slide-to="2" class="carousel-li-2 list-inline-item">3
                        </li>
                    </ul>
                </div>
                <div id="processCarousel" class="carousel slide col-md-6">
                    <div class="carousel-inner box-shadow inside-carousel">
                        <div class="carousel-item active"
                             style="background-image: url(/images/slider/slider1.png);">
                            <div class="circle-slider p-5">
                                <h3 class="text-center text-uppercase">{{ t('Project') }}</h3>
                                <p>{{ t('Establish a project to create data about biodiversity research specimens that have been
                                digitally imaged and for which you have a compelling use.  As you circumscribe the project,
                                look for ways to align its goals with the interests of existing organizations (e.g.,
                                enthusiast groups or educators).  A public page is minted for each project, at which
                                visualizations and useful resources for participants (e.g., lesson plans) can be shared.') }}</p>
                            </div>
                        </div>
                        <div class="carousel-item" style="background-image: url(/images/slider/slider2.png);">
                            <div class="circle-slider p-5">
                                <h3 class="text-center text-uppercase">{{ t('Expeditions') }}</h3>
                                <p>{{ t('If you have many specimens from which you need data, circumscribe subsets using what you
                                already know about the specimens or using the output from the BIOSPEX optical character
                                recognition service, which reads text in images.  These subsets—the “expeditions”—can be
                                launched one or more at a time at a crowdsourcing platform.  Perhaps you have a planned
                                public event at which you would like to complete an expedition.  Use the BIOSPEX event
                                scoreboard to follow progress of your event’s teams.') }}</p>
                            </div>
                        </div>
                        <div class="carousel-item" style="background-image: url(/images/slider/slider3.png);">
                            <div class="circle-slider p-5">
                                <h3 class="text-center text-uppercase">{{ t('Export') }}</h3>
                                <p>{{ t('Use the BIOSPEX admin tools, including leaderboards and summary statistics, to
                                rally participants and follow progress as expeditions proceed.  Upon expedition completion,
                                download data for use and export back to the collections that curate the physical specimens
                                so that everyone can benefit from your project’s work.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tutorial Section -->
    <section class="tutorial">
        <div class="container" style="position:relative;">
            <img src="/images/page/arrow-curved.svg" alt="-->" class="home-arrow d-none d-sm-none d-md-block">
            <div class="row p-5">

                <div class="col-sm-6 mt-5 p-1 pb-md-3">
                    <h2 class="home-header-cta">{{ t('A Project') }}</h2>

                    @if($expedition)
                    <div class="card mb-5 px-4 box-shadow" data-aos="fade-down" data-aos-easing="ease-in"
                         data-aos-duration="2000" data-aos-once="true">
                        <h2 class="text-center pt-4">{{ $expedition->project->title }}</h2>
                        <hr>
                        <div class="row card-body pb-2">
                            <div class="col-12">
                                <div class="col-4 float-right">
                                    <img class="img-fluid"
                                         src="{{ $expedition->project->present()->show_logo }}"
                                         alt="{{ $expedition->project->present()->logo_alt }}">
                                </div>
                                <p>{{ $expedition->project->description_short }}</p>
                            </div>

                            <div class="col-12">
                                <ul class="text">
                                    <li class="mt-3">{{ $expedition->project->expeditions_count }} {{ t('Expeditions') }}</li>
                                    <li>{{ $expedition->project->expedition_stats_sum_transcriptions_completed }} {{ t('Digitizations') }}</li>
                                    <li>{{ get_project_transcriber_count($expedition->project->id) }} {{ t('Participants') }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card-footer pb-4">
                            <div class="d-flex align-items-start justify-content-between mt-4">
                                {!! $expedition->project->present()->project_page_icon_lrg !!}
                                {!! $expedition->project->present()->project_events_icon_lrg !!}
                                {!! $expedition->project->present()->organization_icon_lrg !!}
                                {!! $expedition->project->present()->twitter_icon_lrg !!}
                                {!! $expedition->project->present()->facebook_icon_lrg !!}
                                {!! $expedition->project->present()->blog_icon_lrg !!}
                                {!! $expedition->project->present()->contact_email_icon_lrg !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    <h2>{{ t('The Progress') }}</h2>

                    <div class="card mb-4 px-4 box-shadow" data-aos="fade-right" data-aos-easing="ease-in"
                         data-aos-duration="2000" data-aos-once="true">
                        <h2 class="text-center pt-4">{{ t('BIOSPEX Stats') }}</h2>
                        <hr>
                        <div class="row card-body pb-5">
                            <div class="col-12">
                                <ul class="text">
                                    <li class="mt-3">{{ $transcriptionCount }} {{ t('Digitizations in Biospex-launched Projects') }}</li>
                                    <li>{{ $contributorCount }} {{ t('Participants in Biospex-launched Projects') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 p-1 p-md-5 tutorial-right-section">
                    <h2 class="home-header-cta flex-nowrap">An Expedition</h2>
                    @if($expedition)
                    <div class="card black mb-4 box-shadow" data-aos="fade-up" data-aos-duration="1500"
                         data-aos-anchor-placement="bottom-bottom" data-aos-once="true">
                        <div class="card-top m-0 p-0">
                            <img class="card-img-top" style="max-height: 100%"
                                 src="{{ $expedition->present()->show_medium_logo }}"
                                 alt="{{ $expedition->present()->logo_alt }}">
                            <div class="card-img-overlay">
                                <h2 class="card-title text-center pt-4">{{ $expedition->title }}</h2>
                                <i class="card-info fas fa-info-circle fa-2x float-right"
                                   style="top: 20rem; left: 31.25rem;"></i>
                                <p>{{ $expedition->description }}</p>
                            </div>
                        </div>

                        <div class="card-body white"
                             style="border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
                            <div class="d-flex justify-content-between">
                                <div class="p-2">
                                    <p>{{ $expedition->stat->local_transcriptions_completed }} {{ t('Digitizations') }}</p>
                                </div>
                                <div class="p-2">
                                    <p>{{ $expedition->stat->transcriber_count }} {{ t('Participants') }}</p>
                                </div>
                                <div class="p-2"><p>{{ $expedition->stat->percent_completed }}
                                        % {{ t('Completed') }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex align-items-start justify-content-between mt-4 mx-auto">
                                {!! $expedition->project->present()->project_page_icon_lrg !!}
                                @isset($expedition->panoptesProject)
                                    {!! $expedition->panoptesProject->present()->url_lrg !!}
                                @endisset
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            mirror: true
        });
    </script>
    <script src="https://cdn.jsdelivr.net/gh/cferdinandi/smooth-scroll/dist/smooth-scroll.polyfills.min.js"></script>
    <script>var scroll = new SmoothScroll('a[href*="#"]');</script>

@endpush