@php($banners = [
    'banner-desert.jpg' => t('Desert Banner'),
    'banner-grass.jpg' => t('Grass Banner'),
    'banner-meadow.jpg' => t('Meadow Banner'),
    'banner-mtn-flowers.jpg' => t('Mountain Banner'),
    'banner-swamp.jpg' => t('Swamp Banner'),
    'banner-trees.jpg' => t('Trees Banner'),
])

<x-modal id="project-banner-modal" :title="t('Project Banner')" title-id="projectBannerModalTitle" title-class="text-uppercase">
    @foreach($banners as $file => $alt)
        <div class="m-4">
            <div class="project-banner" data-name="{{ $file }}" data-hover="tooltip"
                 title="{{ t('Click to select banner.') }}">
                <img class="img-fluid" alt="{{ $alt }}" src="{{ '/images/habitat-banners/'.$file }}">
            </div>
        </div>
    @endforeach

    <x-slot:footer>
        <button type="button" class="btn btn-outline-primary color-action align-self-center text-uppercase"
                data-modal-dismiss>{{ t('Exit') }}</button>
    </x-slot:footer>
</x-modal>
