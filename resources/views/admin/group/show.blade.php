@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Group') }} {{ $group->title }}
@stop

@push('styles')
    <link href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css"
          rel="stylesheet"/>
@endpush
{{-- Content --}}
@section('content')
    @include('admin.group.partials.group-panel')

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card white px-4 box-shadow h-100">
                <h3 class="text-center pt-4">{{ t('Members') }}</h3>
                <hr>
                <div class="color-action text-center">{{ t('Use shift + click to multi-sort') }}</div>
                <div class="row card-body">
                    <p>{{ t('Group Owner') }}: {{ $group->owner->present()->full_name_or_email }}</p>
                    @if($group->users->isEmpty())
                        <p class="text-center">{{ t('No users') }}</p>
                    @else
                        <table id="members-tbl" class="table table-striped table-bordered dt-responsive nowrap"
                               style="width:100%; font-size: .8rem">
                            <thead>
                            <tr>
                                <th style="width: 5%"></th>
                                <th>{{ t('Member') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($group->users as $user)
                                <tr>
                                    <td><a href="{{ route('admin.groups-user.destroy', [$group, $user]) }}"
                                           class="prevent-default"
                                           title="{{ t('Delete Member') }}"
                                           aria-label="{{ t('Delete Member') }}"
                                           data-hover="tooltip"
                                           data-method="delete"
                                           data-confirm="confirmation"
                                           data-title="{{ t('Delete Member') }}?"
                                           data-content="{{ t('This will permanently delete the member') }}">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            <span class="sr-only">{{ t('Delete Member') }}</span>
                                            </a>
                                        </td>
                                        <td>{{ $user->present()->full_name_or_email }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card white px-4 box-shadow h-100">
                <h3 class="text-center pt-4">{{ t('Projects') }}</h3>
                <hr>
                <div class="color-action text-center">{{ t('Use shift + click to multi-sort') }}</div>
                <div class="row card-body">
                    @if($group->projects->isEmpty())
                        <p class="text-center">{{ t('No Projects Exist') }}</p>
                    @else
                        <table id="projects-tbl" class="table table-striped table-bordered dt-responsive nowrap"
                               style="width:100%; font-size: .8rem">
                            <thead>
                            <tr>
                                <th>{{ t('Title') }}</th>
                                <th>{{ t('Description') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @each('admin.group.partials.project-loop', $group->projects, 'project')
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card white px-4 box-shadow h-100">
                <h3 class="text-center pt-4">{{ t('Expeditions') }}</h3>
                <hr>
                <div class="color-action text-center">{{ t('Use shift + click to multi-sort') }}</div>
                <div class="row card-body">
                    @if($group->expeditions->isEmpty())
                        <p class="text-center">{{ t('No Expeditions') }}</p>
                    @else
                        <table id="expeditions-tbl" class="table table-striped table-bordered dt-responsive nowrap"
                               style="width:100%; font-size: .8rem">
                            <thead>
                            <tr>
                                <th>{{ t('Title') }}</th>
                                <th>{{ t('Status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @each('admin.group.partials.expedition-loop', $group->expeditions, 'expedition')
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card white px-4 box-shadow h-100">
                <h3 id="geolocate-forms" class="text-center pt-4">{{ t('GeoLocateExport Forms') }}</h3>
                <hr>
                <div class="color-action text-center">{{ t('Use shift + click to multi-sort') }}</div>
                <div class="row card-body">
                    @if($group->geoLocateForms->isEmpty())
                        <p class="text-center">{{ t('No GeoLocateExport Forms Exist') }}</p>
                    @else
                        <table id="geolocate-tbl" class="table table-striped table-bordered dt-responsive nowrap"
                               style="width:100%; font-size: .8rem">
                            <thead>
                            <tr>
                                <th></th>
                                <th>{{ t('Name') }}</th>
                                <th>{{ t('# Assigned Expeditions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($group->geoLocateForms as $form)
                                @include('admin.group.partials.geolocate-loop')
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js"></script>
    @if($group->users->isNotEmpty())
        <script>
            $('#members-tbl').DataTable();
        </script>
    @endif
    @if($group->projects->isNotEmpty())
        <script>
            $('#projects-tbl').DataTable();
        </script>
    @endif
    @if($group->expeditions->isNotEmpty())
        <script>
            $('#expeditions-tbl').DataTable();
        </script>
    @endif
    @if($group->geoLocateForms->isNotEmpty())
        <script>
            $('#geolocate-tbl').DataTable();
        </script>
    @endif
@endpush