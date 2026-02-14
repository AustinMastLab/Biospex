<div class="row">
    <div class="col-sm-10 mx-auto">
        <div class="jumbotron box-shadow pt-2 pb-5 my-5 p-sm-5">
            <h1 class="page-title text-center content-header text-uppercase">{{ $bingo->title }}</h1>
            <p class="text-center">{{ $bingo->directions }}</p>
            <div class="col-md-12 d-flex">
                <div class="col-md-6">
                    <p>{{ t('Project') }}
                        :
                        <a href="{{ route('front.projects.show', ['slug' => $bingo->project->slug]) }}">{{ $bingo->project->title }}</a>
                    </p>
                </div>
            </div>
            <div class="col-md-12 d-flex justify-content-between mt-4 mb-3">
                {!! $bingo->project->present()->project_page_icon_lrg !!}
                @can('isOwner', $bingo)
                    {!! $bingo->present()->edit_icon_lrg !!}
                    {!! $bingo->present()->delete_icon_lrg !!}
                @endcan
            </div>
        </div>
    </div>
</div>
