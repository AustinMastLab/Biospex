@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Group') }} {{ $group->title }}
@stop

{{-- Content --}}
@section('content')
    @include('admin.group.partials.group-panel')

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card white px-4 box-shadow h-100">
                <h3 class="text-center pt-4">{{ t('Members') }}</h3>
                <hr>
                <div class="row card-body">
                    <p>{{ t('Group Owner') }}: {{ $group->owner->present()->full_name_or_email }}</p>
                    @if($group->users->isEmpty())
                        <p class="text-center">{{ t('No users') }}</p>
                    @else
                        <div class="data-table-scroll" tabindex="0" role="region" aria-label="{{ t('Members') }}">
                        <table id="members-tbl" class="table table-striped table-bordered data-table">
                            <thead>
                            <tr>
                                <th scope="col" style="width: 5%"><span class="sr-only">{{ t('Remove') }}</span></th>
                                <th scope="col">{{ t('Member') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($group->users->sortBy(fn ($user) => mb_strtolower($user->present()->full_name_or_email)) as $user)
                                    <tr>
                                        <td>
                                            {!! $group->present()->groupRemoveMemberIcon($user) !!}
                                        </td>
                                        <td>{{ $user->present()->full_name_or_email }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card white px-4 box-shadow h-100">
                <h3 class="text-center pt-4">{{ t('Projects') }}</h3>
                <hr>
                <div class="row card-body">
                    @if($group->projects->isEmpty())
                        <p class="text-center">{{ t('No Projects Exist') }}</p>
                    @else
                        <table id="projects-tbl" class="table table-striped table-bordered data-table">
                            <thead>
                            <tr>
                                <th scope="col">{{ t('Title') }}</th>
                                <th scope="col">{{ t('Description') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @each('admin.group.partials.project-loop', $group->projects->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE), 'project')
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
                <div class="row card-body">
                    @if($group->expeditions_count === 0)
                        <p class="text-center">{{ t('No Expeditions') }}</p>
                    @else
                        <livewire:admin.group-expeditions-table :group="$group"/>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card white px-4 box-shadow h-100">
                <h3 id="geolocate-forms" class="text-center pt-4">{{ t('GeoLocateExport Forms') }}</h3>
                <hr>
                <div class="row card-body">
                    @if($group->geo_locate_forms_count === 0)
                        <p class="text-center">{{ t('No GeoLocateExport Forms Exist') }}</p>
                    @else
                        <livewire:admin.group-geo-locate-forms-table :group="$group"/>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
