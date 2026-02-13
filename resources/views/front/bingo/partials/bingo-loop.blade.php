<div class="col-md-4 mb-4">
    <div class="card px-4 box-shadow h-100">
        <div class="card-body text-center">
            <h4 class="text-center pt-4">{{ $bingo->title }}</h4>
            <h5 class="text-center color-action">
                {{ $bingo->present()->create_date_to_string }}
                {{ t('for') }}<br>
                {{ $project->title }}
            </h5>
            {!! $bingo->present()->generate_card_button !!}
        </div>
        <div class="card-footer">
            <div class="d-flex align-items-start justify-content-between mt-4 mb-3">
                {!! $project->present()->project_page_icon !!}
                {!! $bingo->present()->show_icon !!}
                {!! $bingo->present()->twitter_icon !!}
                {!! $bingo->present()->facebook_icon !!}
                {!! $bingo->present()->contact_icon !!}
            </div>
        </div>
    </div>
</div>
