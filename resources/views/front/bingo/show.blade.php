@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Bingo') }}
@stop

@section('header')
    <header id="header-img" style="background-image: url(/images/page-banners/banner-games.jpg);">
        <nav class="header navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

{{-- Content --}}
@section('content')
    @if($bingo === null)
        <div class="alert alert-danger text-center">
            {{ t('Bingo not found') }}
        </div>
    @else
    <h1 class="page-title text-center pt-4 text-uppercase">{{ $bingo->title }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <div class="d-flex align-items-center justify-content-center ">
        @include('front.bingo.partials.bingo-loop', ['project' => $bingo->project])
    </div>
    <div class="row">
        @include('front.bingo.partials.words-table')
    </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $('#words-tbl').DataTable();
    </script>
@endpush
