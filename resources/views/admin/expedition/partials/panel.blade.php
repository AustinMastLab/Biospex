<div class="row">
    <div class="col-sm-10 mx-auto">
        <div class="jumbotron box-shadow pt-2 pb-5 my-5 p-sm-5">
            <h1 class="page-title text-center content-header text-uppercase">{{ $expedition->title }}</h1>
            <div class="col-12">
                <div class="d-flex justify-content-between mt-4 mb-3">
                    {!! $expedition->project->present()->project_admin_icon_lrg !!}
                    {!! $expedition->present()->expedition_show_icon_lrg !!}
                    @if($expedition->downloads->isNotEmpty())
                        {!! $expedition->present()->expedition_download_icon_lrg !!}
                    @endif
                    {!! $expedition->present()->expedition_tools_icon_lrg !!}
                    {!! $expedition->present()->expedition_edit_icon_lrg !!}
                    {!! $expedition->present()->expedition_clone_icon_lrg !!}
                    @can('isOwner', $expedition->project->group)
                        {!! $expedition->present()->expedition_delete_icon_lrg !!}
                    @endcan
                </div>
                <hr class="header mx-auto" style="width:300px;">
                <div class="d-flex justify-content-between mt-4">
                    <span class="text">{{ t('Biospex Subjects') }} {{ $expedition->stat->local_subject_count }}</span>
                    <span class="text">{{ t('Zooniverse Subjects') }} {{ $expedition->stat->subject_count }}</span>
                    <span class="text">{{ t('Digitization Goal') }} {{ $expedition->stat->transcriptions_goal }}</span>
                    <span class="text">{{ t('Digitizations Completed') }} {{ $expedition->stat->percent_completed }}%</span>
                </div>
            </div>
        </div>
    </div>
</div>
