@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Expeditions') }}
@stop

{{-- Content --}}
@section('content')
    <h1 class="page-title text-center pt-4 text-uppercase">{{ t('Biospex Expeditions') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <h2 class="sr-only">{{ t('Expeditions list') }}</h2>
    <livewire:admin.expeditions-index />
@endsection