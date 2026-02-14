@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ $project->title }}
@stop

{{-- Content --}}
@section('content')
    @include('admin.project.partials.project-panel')
    <div class="row">
        <div class="col-sm-8 offset-md-2">
            <h2 class="text-center content-header text-uppercase" id="expeditions">{{ t('Expeditions') }}</h2>
            <hr class="header mx-auto" style="width:300px;">
            <div class="text-center mt-4">
                <button class="toggle-view-btn btn btn-primary pl-4 pr-4 text-uppercase"
                        data-toggle="collapse"
                        data-target="#active-expeditions-main,#completed-expeditions-main"
                        data-value="{{ t('view active expeditions') }}"
                >{{ t('view completed expeditions') }}</button>
            </div>
            <div class="d-flex justify-content-between mt-4 mb-3">
                <span>{{ $project->expeditions_count }} {{ t('Expeditions') }}</span>
                <span>{{ $project->expedition_stats_sum_transcriptions_completed }} {{ t('Digitizations') }}</span>
                <span>{{ get_project_transcriber_count($project->id) }} {{ t('Participants') }}</span>
            </div>
        </div>
        <div id="active-expeditions-main" class="col-sm-12 show">
            <livewire:admin.expeditions-index type="active" :project-id="$project->id" />
        </div>
        <div id="completed-expeditions-main" class="col-sm-12 collapse">
            <livewire:admin.expeditions-index type="completed" :project-id="$project->id" />
        </div>
    </div>
@endsection

