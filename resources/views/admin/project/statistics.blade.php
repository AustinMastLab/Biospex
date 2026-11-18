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
                        <div id="statDiv" style="width: 100%; height: 600px; color: #000000; font-size: 0.8em"></div>

                        <p id="statDiv-desc" class="sr-only">
                            Bar chart showing digitizations and the number of participants per digitization count.
                            A table with the same values follows.
                        </p>

                        <div id="statDiv-a11y-table" class="sr-only" aria-describedby="statDiv-desc"></div>
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
        @include('common.amchart')
        @vite('resources/js/amChartStat-entry.js')
    @endif
@endpush
