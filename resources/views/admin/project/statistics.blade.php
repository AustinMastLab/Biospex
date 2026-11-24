@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ $project->title }} {{ t('Statistics') }}
@stop

@section('content')
    @include('admin.project.partials.project-panel')
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card white px-4 box-shadow h-100">
                <h3 class="text-center pt-4">{{ t('Participant Summary') }}</h3>
                <hr>
                @if($transcribers->isEmpty())
                    <p class="text-center">{{ t('No digitizations exist.') }}</p>
                @else
                    <div class="row card-body">
                        <livewire:admin.project-participants-table :project="$project"/>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card white px-4 box-shadow h-100">
                <h3 class="text-center pt-4">{{ t('Digitizations') }}</h3>
                <hr>
                @if(isset($transcriptions))
                    <div class="row card-body">
                        <div id="statDiv" aria-hidden="true" style="width: 100%; height: 600px"></div>
                        <details class="mt-3 w-100">
                            <summary>{{ t('Digitizations data table') }}</summary>
                            <div id="statDiv-table" class="mt-2"></div>
                        </details>
                    </div>
                @else
                    <p class="text-center">{{ t('No digitizations exist.') }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    @if(isset($transcriptions))
        @vite('resources/js/charts/stat-chart.js')
    @endif
@endpush
