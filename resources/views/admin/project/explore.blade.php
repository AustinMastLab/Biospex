@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Explore Subjects') }}
@stop

{{-- Content --}}
@section('content')
    @include('admin.project.partials.project-panel')
    <div class="row align-items-center mb-3">
        <div class="col-lg-5 mb-2 mb-lg-0">
            <a href="{{ route('admin.project-subjects.destroy', [$project]) }}"
               class="btn btn-primary text-uppercase prevent-default"
               title="{{ t('Delete Unassigned Subjects') }}"
               data-hover="tooltip"
               data-method="delete"
               data-confirm="confirmation"
               data-title="{{ t('Delete Unassigned Subjects') }}?"
               data-content="{{ t('This will permanently delete all unassigned subjects from the project and database.') }}">
                <i class="fas fa-minus-circle"></i> {{ t('Delete Unassigned Subjects') }}</a>
        </div>
        <div class="col-lg-7">
            <h3 class="mb-0">{{ t('Subjects currently assigned') }}: <span
                        id="subjectCount">{{ $subjectAssignedCount }}</span></h3>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <livewire:admin.subject-grid :project="$project" mode="explore"/>
        </div>
    </div>
@endsection
