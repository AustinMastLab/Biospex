@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Events') }}
@stop

{{-- Content --}}
@section('content')
    <h1 class="page-title text-center text-uppercase pt-4">{{ t('Biospex Events') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <div class="row">
        <div class="col-sm-8 offset-md-2 text-center">
            <button class="toggle-view-btn btn btn-primary my-4 mr-2 text-uppercase"
                    data-toggle="collapse"
                    data-target="#active-events-main,#completed-events-main"
                    data-value="{{ t('view active events') }}"
            >{{ t('view completed events') }}</button>
            <a href="{{ route('admin.events.create') }}" type="submit"
               class="btn btn-primary my-4 ml-2 text-uppercase"><i class="fas fa-plus-circle"></i> {{ t('New Event') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div id="active-events-main" class="col-sm-12 show">
            <livewire:admin.events-index type="active" />
        </div>
        <div id="completed-events-main" class="col-sm-12 collapse">
            <livewire:admin.events-index type="completed" />
        </div>
    </div>
    @include('common.scoreboard')
    @include('common.event-step-chart')
@endsection

@push('scripts')
    <script src="{{ asset('js/amChartEventRate.min.js')}}"></script>
@endpush