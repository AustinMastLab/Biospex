<div class="mx-auto mb-4">
    <div class="card team px-4 box-shadow h-100" style="max-width: 25rem;">
        <h2 class="text-center pt-4">{{ $team->present()->full_name }}</h2>
        <hr>
        <p class="text-center"><strong>{{ $team->title }}</strong><br>
            {{ $team->department }}<br>
        <h3 class="pb-3 text-center color-action">{{ $team->institution }}</h3>

        <div class="card-footer">
            <div class="d-flex align-items-start justify-content-between mt-4 mb-3">
                <a
                    href="mailto:{{ $team->email }}"
                    aria-label="{{ t('Email') }} {{ $team->present()->full_name }}"
                >
                    <i class="fas fa-envelope fa-2x" aria-hidden="true"></i>
                    <span class="sr-only">{{ t('Email') }} {{ $team->present()->full_name }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
