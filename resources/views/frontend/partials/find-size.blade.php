@php
    $isArabic = app()->getLocale() === 'ar';

    $sizeFinderText = [
        'title' => $isArabic ? 'اعثر على مقاسك المناسب' : 'Find your best size',
        'helper' => $isArabic
            ? 'أدخل القياسات المتوفرة لديك، وسنقارنها تلقائيًا مع جدول قياسات هذا المنتج.'
            : 'Enter the measurements you have, and we will compare them with this product size chart.',
        'fieldSuffix' => $isArabic ? 'سم' : 'cm',
        'submit' => $isArabic ? 'اقترح المقاس' : 'Suggest size',
        'reset' => $isArabic ? 'مسح القيم' : 'Clear values',
        'empty' => $isArabic ? 'أدخل قيمة واحدة على الأقل للحصول على اقتراح.' : 'Enter at least one value to get a suggestion.',
        'unavailable' => $isArabic ? 'لا توجد حقول قياس كافية لهذا المنتج.' : 'There are not enough measurement fields for this product.',
        'resultPrefix' => $isArabic ? 'المقاس الأنسب لك غالبًا هو' : 'Your best suggested size is',
        'nearestPrefix' => $isArabic ? 'أقرب مقاس حسب القيم المدخلة هو' : 'The closest size based on your values is',
        'select' => $isArabic ? 'اختيار هذا المقاس' : 'Select this size',
        'selected' => $isArabic ? 'تم اختيار المقاس المقترح.' : 'The suggested size has been selected.',
        'notSelectable' => $isArabic ? 'المقاس المقترح غير متاح للاختيار حاليًا.' : 'The suggested size is not currently available to select.',
        'note' => $isArabic
            ? 'الاقتراح إرشادي ويعتمد على البيانات المتوفرة في جدول هذا المنتج.'
            : 'This suggestion is guidance based on the available data in this product chart.',
    ];
@endphp

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
                        <div class="size-finder-guide-card">
                            <img
                                src=""
                                alt="{{ __('front.products.size_chart') }}"
                                class="img-fluid w-100"
                                data-size-chart-guide-image
                            >
                        </div>
                    </div>
                    <div class="col-12 col-lg-8" data-size-chart-table-wrap>
                        <div class="size-finder-card mb-4" data-size-finder-panel
                            data-title="{{ $sizeFinderText['title'] }}"
                            data-helper="{{ $sizeFinderText['helper'] }}"
                            data-field-suffix="{{ $sizeFinderText['fieldSuffix'] }}"
                            data-submit="{{ $sizeFinderText['submit'] }}"
                            data-reset="{{ $sizeFinderText['reset'] }}"
                            data-empty="{{ $sizeFinderText['empty'] }}"
                            data-unavailable="{{ $sizeFinderText['unavailable'] }}"
                            data-result-prefix="{{ $sizeFinderText['resultPrefix'] }}"
                            data-nearest-prefix="{{ $sizeFinderText['nearestPrefix'] }}"
                            data-select="{{ $sizeFinderText['select'] }}"
                            data-selected="{{ $sizeFinderText['selected'] }}"
                            data-not-selectable="{{ $sizeFinderText['notSelectable'] }}"
                            data-note="{{ $sizeFinderText['note'] }}">
                            <div class="size-finder-card__head">
                                <div>
                                    <h6 class="size-finder-card__title">{{ $sizeFinderText['title'] }}</h6>
                                    <p class="size-finder-card__helper">{{ $sizeFinderText['helper'] }}</p>
                                </div>
                            </div>
                            <div class="size-finder-fields" data-size-finder-fields></div>
                            <div class="size-finder-actions">
                                <button type="button" class="tf-btn btn-fill size-finder-submit" data-size-finder-submit>
                                    {{ $sizeFinderText['submit'] }}
                                </button>
                                <button type="button" class="size-finder-reset" data-size-finder-reset>
                                    {{ $sizeFinderText['reset'] }}
                                </button>
                            </div>
                            <div class="size-finder-result d-none" data-size-finder-result></div>
                        </div>

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

<style>
    .popup-findsize .size-finder-guide-card {
        border: 1px solid #e7e7e7;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
        padding: 10px;
    }

    .popup-findsize .size-finder-guide-card img {
        max-height: 520px;
        object-fit: contain;
    }

    .popup-findsize .size-finder-card {
        border: 1px solid #e7e7e7;
        border-radius: 16px;
        background: #fafafa;
        padding: 18px;
    }

    .popup-findsize .size-finder-card__title {
        margin-bottom: 6px;
        font-size: 16px;
        font-weight: 700;
    }

    .popup-findsize .size-finder-card__helper {
        margin: 0 0 16px;
        color: #666;
        font-size: 13px;
        line-height: 1.7;
    }

    .popup-findsize .size-finder-fields {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .popup-findsize .size-finder-field label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #222;
    }

    .popup-findsize .size-finder-field input {
        width: 100%;
        height: 42px;
        border: 1px solid #dedede;
        border-radius: 10px;
        padding: 0 12px;
        background: #fff;
        direction: ltr;
        text-align: start;
    }

    .popup-findsize .size-finder-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin-top: 16px;
    }

    .popup-findsize .size-finder-submit {
        min-width: 150px;
        height: 42px;
        border-radius: 999px;
    }

    .popup-findsize .size-finder-reset {
        border: 0;
        background: transparent;
        color: #555;
        font-size: 13px;
        font-weight: 700;
        text-decoration: underline;
    }

    .popup-findsize .size-finder-result {
        margin-top: 16px;
        border: 1px solid #111;
        border-radius: 14px;
        background: #fff;
        padding: 14px;
        font-size: 14px;
        line-height: 1.7;
    }

    .popup-findsize .size-finder-result__size {
        font-size: 18px;
        font-weight: 800;
        color: #111;
    }

    .popup-findsize .size-finder-result__note {
        display: block;
        margin-top: 4px;
        color: #777;
        font-size: 12px;
    }

    .popup-findsize .size-finder-result__select {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 10px;
        height: 36px;
        padding: 0 16px;
        border-radius: 999px;
        background: #111;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #111;
    }

    .popup-findsize .tf-sizeguide-table tr.is-recommended td {
        background: #f4f4f4;
        font-weight: 700;
    }

    @media (max-width: 991.98px) {
        .popup-findsize .size-finder-fields {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .popup-findsize .size-finder-card {
            padding: 14px;
        }

        .popup-findsize .size-finder-fields {
            grid-template-columns: 1fr;
        }
    }
</style>
