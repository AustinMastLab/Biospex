@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Projects') }}
@stop

{{-- Content --}}
@section('content')
    <h1 class="page-title text-center pt-4 text-uppercase">{{ t('Biospex Projects') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <div class="row">
        <livewire:admin.projects-index />
    </div>
@endsection
