<div class="modal fade modalDemo tf-product-modal popup-findsize" id="find_size">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="header">
                <div class="demo-title" data-size-chart-title>{{ __('front.products.size_chart') }}</div>
                <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
            </div>
            <div class="tf-rte">
                <div class="tf-table-res-df">
                    <h6 data-size-chart-subtitle>{{ __('front.products.size_guide') }}</h6>
                    <div class="tf-size-chart-empty d-none" data-size-chart-empty>{{ __('front.products.size_chart_empty') }}</div>
                    <table class="tf-sizeguide-table d-none" data-size-chart-table>
                        <thead>
                            <tr data-size-chart-head></tr>
                        </thead>
                        <tbody data-size-chart-body></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
