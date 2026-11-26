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
                {{-- One update open at a time. --}}
                <div id="accordion" x-data="{ openUpdate: null }">
                    @foreach($updates as $update)
                        <div class="card update">
                            <div class="card-header" id="heading{{ $update->id }}">
                                <button type="button" class="update btn text-left p-0"
                                        @click="openUpdate = openUpdate === {{ $update->id }} ? null : {{ $update->id }}"
                                        :aria-expanded="(openUpdate === {{ $update->id }}).toString()"
                                        aria-expanded="false"
                                        aria-controls="collapse{{ $update->id }}">
                                    {{ $update->created_at }} - {{ $update->title }}
                                </button>
                            </div>
                            <div id="collapse{{ $update->id }}"
                                 aria-labelledby="heading{{ $update->id }}"
                                 x-show="openUpdate === {{ $update->id }}"
                                 x-collapse
                                 x-cloak>
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