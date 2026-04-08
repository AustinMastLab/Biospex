@if($projects->isNotEmpty())
    @each('admin.project.partials.project-loop', $projects, 'project')
@else
    <div class="col-12 text-center py-5">
        <h2 class="mb-0">{{ t('No Projects Exist') }}</h2>
    </div>
@endif
