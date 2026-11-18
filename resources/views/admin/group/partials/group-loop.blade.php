<div class="col-md-6 col-lg-4 mb-4">
    <div class="card px-4 box-shadow h-100">
        <h2 class="text-center pt-4">{{ $group->title }}</h2>
        <hr>
        <div class="row card-body">
            <div class="col-sm-6">
                <ul class="text">
                    <li class="smalltext">{{ $group->users_count }} {{ t('Members') }}</li>
                    <li class="smalltext">{{ $group->projects_count }} {{ t('Projects') }}</li>
                    <li class="smalltext">{{ $group->expeditions_count }} {{ t('Expeditions') }}</li>
                </ul>
            </div>
            <div class="col-sm-6">
                <i class="color-action fas fa-users fa-5x"></i>
            </div>
        </div>

        <div class="card-footer">
            <div class="d-flex align-items-start justify-content-between mt-4 mb-3">
                {!! $group->present()->group_invite_icon !!}
                {!! $group->present()->group_show_icon !!}
                {!! $group->present()->group_edit_icon !!}
                @can('isOwner', $group)
                    {!! $group->present()->group_delete_icon !!}
                @endcan
            </div>
        </div>
    </div>
</div>
