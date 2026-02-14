@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Biospex Updates') }}
@stop

{{-- Content --}}
@section('content')
    <h1 class="page-title text-center col-6 mx-auto pt-4 text-uppercase">
        {{ t('Biospex  Updates') }}</h1>
    <hr class="header mx-auto" style="width:300px;">
    <div class="col-12 col-md-10 offset-md-1">
        <div class="jumbotron box-shadow py-5 my-5 p-sm-5">
            <div class="col-sm-12 mb-5">
                <div id="accordion">
                    @foreach($updates as $update)
                        <div class="card update">
                            <div class="card-header" id="heading{{ $update->id }}">
                                <button class="update btn text-left p-0" data-toggle="collapse"
                                        data-target="#collapse{{ $update->id }}" aria-expanded="true"
                                        aria-controls="collapse{{ $update->id }}">
                                    {{ $update->created_at }} - {{ $update->title }}
                                </button>
                            </div>
                            <div id="collapse{{ $update->id }}" class="collapse"
                                 aria-labelledby="heading{{ $update->id }}"
                                 data-parent="#accordion">
                                <div class="card-body">
                                    {!! $update->message !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection