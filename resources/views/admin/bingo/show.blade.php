@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ $bingo->title }}
@stop


{{-- Content --}}
@section('content')
    @include('admin.bingo.partials.bingo-panel')
    @include('admin.bingo.partials.words-table')
@stop
