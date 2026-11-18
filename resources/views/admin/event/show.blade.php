@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ $event->title }}
@stop

{{-- Content --}}
@section('content')
    @include('admin.event.partials.event-panel')
    @include('admin.event.partials.team-table')
@stop
