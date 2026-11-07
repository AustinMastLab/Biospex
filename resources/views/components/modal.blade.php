{{--
    Modal dialog, opened by resources/js/modal.js: a trigger with data-modal-target="#id" opens it, and any element
    with data-modal-dismiss inside it closes it. It keeps Bootstrap's modal markup and classes, so the existing styles
    apply unchanged.

    Props: id; title (header text; no header without it); titleId and titleClass for the heading; size (modal-lg,
    modal-xl or ''); bodyClass; fade (Bootstrap's fade-in); backdrop (false for no dimmed background).
    Slots: the body, an optional subtitle under the title, and an optional footer (default: an Exit button).
--}}
@props([
    'id',
    'title' => null,
    'titleId' => null,
    'titleClass' => '',
    'size' => 'modal-lg',
    'bodyClass' => '',
    'fade' => true,
    'backdrop' => true,
])

@php($titleId ??= $id.'-title')

<dialog id="{{ $id }}" {{ $attributes->class(['modal', 'fade' => $fade, 'modal-no-backdrop' => ! $backdrop])->merge($title !== null ? ['aria-labelledby' => $titleId] : []) }}>
    <div class="modal-dialog modal-dialog-centered {{ $size }}">
        <div class="modal-content">
            @if($title !== null)
                <div class="modal-header">
                    <div class="empty-icon"><i class="fa fa-fw" aria-hidden="true"></i></div>
                    <div>
                        <h2 id="{{ $titleId }}" @class(['color-action', $titleClass => $titleClass !== ''])>{{ $title }}</h2>
                        {{ $subtitle ?? '' }}
                    </div>
                    <div>
                        <button type="button" class="close" data-modal-dismiss aria-label="{{ t('Close') }}">
                            <span><i class="far fa-times-circle" aria-hidden="true"></i></span>
                        </button>
                    </div>
                </div>
            @endif

            <div @class(['modal-body', $bodyClass => $bodyClass !== ''])>{{ $slot }}</div>

            <div class="modal-footer text-center">
                @isset($footer)
                    {{ $footer }}
                @else
                    <button type="button" class="btn btn-outline-primary color-action align-self-center"
                            data-modal-dismiss>{{ t('Exit') }}</button>
                @endisset
            </div>
        </div>
    </div>
</dialog>
