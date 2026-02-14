@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Expeditions') }}
@stop

{{-- Content --}}
@section('content')
    <h1 class="page-title text-center pt-4 text-uppercase">{{ t('Biospex Expeditions') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <div class="row">
        <div class="text-center mx-auto my-4">
            <button class="toggle-view-btn btn btn-primary pl-4 pr-4 text-uppercase"
                    data-toggle="collapse"
                    data-target="#active-expeditions-main,#completed-expeditions-main"
                    data-value="{{ t('view active expeditions') }}"
            >{{ t('view completed expeditions') }}</button>
        </div>
    </div>
    <div class="row">
        <div id="active-expeditions-main" class="col-sm-12 show">
            <livewire:admin.expeditions-index type="active" />
        </div>
        <div id="completed-expeditions-main" class="col-sm-12 collapse">
            <livewire:admin.expeditions-index type="completed" />
        </div>
    </div>
@endsection