<!-- Modal -->
<div class="modal fade" id="step-chart-modal" tabindex="-1" role="dialog" aria-labelledby="stepChartModalTitle">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="empty-icon"><i class="fa fa-fw" aria-hidden="true"></i></div>
                <div><h2 id="stepChartModalTitle" class="color-action">{{ t('Rate Chart') }}</h2></div>
                <div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span><i class="far fa-times-circle" aria-hidden="true"></i></span>
                    </button>
                </div>
            </div>

            <div class="modal-body">
                <div class="jumbotron box-shadow m-5">
                    <div
                        id="chartdiv"
                        class="d-flex"
                        style="width:100%; height: 500px"
                        role="img"
                        aria-label="Rate chart"
                        aria-describedby="event-rate-chart-desc"
                    ></div>
                    <p id="event-rate-chart-desc" class="sr-only">
                        Line chart showing estimated records per hour over time by team. The chart updates periodically.
                    </p>
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