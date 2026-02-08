<!-- Modal -->
<div class="modal fade" id="process-modal" tabindex="-1" role="dialog" aria-labelledby="process-modal-label">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="empty-icon"><i class="fa fa-fw" aria-hidden="true"></i></div>
                <div>
                    <h2 class="color-action" id="process-modal-label">{{ t('Processes') }}</h2>
                </div>
                <div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span><i class="far fa-times-circle" aria-hidden="true"></i></span>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                @livewire('process-monitor')
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
