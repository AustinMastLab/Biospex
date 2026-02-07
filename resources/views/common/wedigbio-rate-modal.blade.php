<!-- Modal -->
<div class="modal fade" id="wedigbio-rate-modal" tabindex="-1" role="dialog"
     aria-labelledby="wedigbio-rate-modal-title">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="empty-icon"><i class="fa fa-fw" aria-hidden="true"></i></div>
                <div>
                    <h2 class="color-action" id="wedigbio-rate-modal-title">{{ t('WEDIGBIO RATE') }}</h2>
                    <span class="text-danger pl-2">{{ t('updates every 5 minutes') }}</span>
                </div>
                <div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span><i class="far fa-times-circle" aria-hidden="true"></i></span>
                    </button>
                </div>
            </div>

            <div class="modal-body">
                <div class="jumbotron box-shadow m-5">
                    <div id="weDigBioRateChartDiv" class="d-flex justify-content-center" style="width:100%; height: 500px"></div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" class="btn btn-outline-primary color-action align-self-center"
                        data-dismiss="modal">{{ t('Exit') }}
                </button>
            </div>
        </div>
    </div>
</div>
<!-- end modal -->
