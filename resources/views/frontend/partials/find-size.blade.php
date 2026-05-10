<div class="modal fade modalDemo tf-product-modal popup-findsize" id="find_size">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: min(1200px, 95vw);">
        <div class="modal-content">
            <div class="header">
                <div class="demo-title" data-size-chart-title>{{ __('front.products.size_chart') }}</div>
                <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
            </div>
            <div class="tf-rte">
                <div class="row g-4 align-items-start">
                    <div class="col-12 col-lg-4 d-none" data-size-chart-guide-wrap>
                        <div class="border rounded-3 overflow-hidden bg-white">
                            <img
                                src=""
                                alt="{{ __('front.products.size_chart') }}"
                                class="img-fluid w-100"
                                data-size-chart-guide-image
                                style="max-height: 520px; object-fit: contain;"
                            >
                        </div>
                    </div>
                    <div class="col-12 col-lg-8" data-size-chart-table-wrap>
                        <div class="tf-table-res-df">
                            <h6 data-size-chart-subtitle>{{ __('front.products.size_guide') }}</h6>
                            <div class="tf-size-chart-empty d-none" data-size-chart-empty>{{ __('front.products.size_chart_empty') }}</div>
                            <div style="width: 100%; overflow-x: auto; overflow-y: hidden;">
                                <table class="tf-sizeguide-table d-none" data-size-chart-table style="min-width: 900px;">
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
        </div>
    </div>
</div>
