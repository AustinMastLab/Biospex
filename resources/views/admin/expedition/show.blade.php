@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ $expedition->title }}
@stop

{{-- Content --}}
@section('content')
    @include('admin.expedition.partials.panel')
    <div class="row">
        <h3 class="mx-auto">{{ t('Subjects currently assigned') }}:
            {{ $expedition->stat->local_subject_count }}
        </h3>

        <div class="col-md-12">
            <table class="table table-bordered" id="jqGridTable" data-a11y-label="{{ t('Expedition Subjects') }}"></table>
        </div>
    </div>
@endsection
