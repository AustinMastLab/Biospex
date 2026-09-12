@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Events') }}
@stop

{{-- Content --}}
@section('content')
    <h1 class="page-title text-center text-uppercase pt-4">{{ t('Biospex Events') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <h2 class="sr-only">{{ t('Events list') }}</h2>
    <livewire:admin.events-index />
    @include('common.scoreboard')
    @include('common.event-step-chart')
@endsection

@push('scripts')
    <script src="{{ asset('js/amChartEventRate.min.js')}}"></script>
@endpush