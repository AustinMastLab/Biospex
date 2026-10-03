<div class="col-md-4 mb-4">
    <div class="card px-4 box-shadow h-100">
        <h2 class="text-center pt-4">{{ $project->title }}</h2>
        <hr>
        <div class="row card-body pb-2">
            <div class="col-12">
                <div class="col-4 float-right">
                    <img class="img-fluid" src="{{ $project->present()->show_logo }}" onerror="this.onerror=null;this.src='{{ config('config.missing_project_logo') }}'" alt="Card image cap">
                </div>
                <p class="smalltext">{{ $project->description_short }}</p>
            </div>
            <div class="col-12">
                <ul class="text">
                    <li class="smalltext">{{ $project->expeditions_count }} {{ t('Expeditions') }}</li>
                    <li class="smalltext">{{ $project->expedition_stats_sum_transcriptions_completed }} {{ t('Digitizations') }}</li>
                    <li class="smalltext">{{ get_project_transcriber_count($project->id) }} {{ t('Participants') }}</li>
                </ul>
            </div>

        </div>

        <div class="card-footer">
            <div class="d-flex align-items-start justify-content-between mt-4 mb-3">
                {!! $project->present()->project_page_icon !!}
                {!! $project->present()->project_events_icon !!}
                {!! $project->present()->organization_icon !!}
                {!! $project->present()->twitter_icon !!}
                {!! $project->present()->facebook_icon !!}
                {!! $project->present()->blog_icon !!}
                {!! $project->present()->contact_email_icon !!}
            </div>
        </div>
    </div>
</div>