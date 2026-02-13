<!-- Modal -->
<div class="modal fade" id="project-banner-modal" tabindex="-1" role="dialog" aria-labelledby="projectBannerModalTitle"
     >
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="empty-icon"><i class="fa fa-fw"></i></div>
                <div><h2 id="projectBannerModalTitle" class="color-action text-uppercase">{{ t('Project Banner') }}</h2></div>
                <div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span ><i class="far fa-times-circle"></i></span>
                    </button>
                </div>
            </div>

            <div class="modal-body">
                <div class="m-4">
                    <div class="project-banner" data-name="banner-desert.jpg" data-hover="tooltip"
                         title="{{ t('Click to select banner.') }}">
                        <img class="img-fluid" alt="{{ t('Desert Banner') }}"
                             src="{{ '/images/habitat-banners/banner-desert.jpg' }}">
                    </div>
                </div>

                <div class="m-4">
                    <div class="project-banner" data-name="banner-grass.jpg" data-hover="tooltip"
                         title="{{ t('Click to select banner.') }}">
                        <img class="img-fluid" alt="{{ t('Grass Banner') }}"
                             src="{{ '/images/habitat-banners/banner-grass.jpg' }}">
                    </div>
                </div>

                <div class="m-4">
                    <div class="project-banner" data-name="banner-meadow.jpg" data-hover="tooltip"
                         title="{{ t('Click to select banner.') }}">
                        <img class="img-fluid" alt="{{ t('Meadow Banner') }}"
                             src="{{ '/images/habitat-banners/banner-meadow.jpg' }}">
                    </div>
                </div>

                <div class="m-4">
                    <div class="project-banner" data-name="banner-mtn-flowers.jpg" data-hover="tooltip"
                         title="{{ t('Click to select banner.') }}">
                        <img class="img-fluid" alt="{{ t('Mountain Banner') }}"
                             src="{{ '/images/habitat-banners/banner-mtn-flowers.jpg' }}">
                    </div>
                </div>

                <div class="m-4">
                    <div class="project-banner" data-name="banner-swamp.jpg" data-hover="tooltip"
                         title="{{ t('Click to select banner.') }}">
                        <img class="img-fluid" alt="{{ t('Swamp Banner') }}"
                             src="{{ '/images/habitat-banners/banner-swamp.jpg' }}">
                    </div>
                </div>

                <div class="m-4">
                    <div class="project-banner" data-name="banner-trees.jpg" data-hover="tooltip"
                         title="{{ t('Click to select banner.') }}">
                        <img class="img-fluid" alt="{{ t('Trees Banner') }}"
                             src="{{ '/images/habitat-banners/banner-trees.jpg' }}">
                    </div>
                </div>
            </div>

            <div class="modal-footer text-center">
                <button type="button" class="btn btn-outline-primary color-action align-self-center text-uppercase"
                        data-dismiss="modal">{{ t('Exit') }}
                </button>
            </div>
        </div>
    </div>
</div>
<!-- end modal -->
