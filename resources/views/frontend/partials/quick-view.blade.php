<style>
    #quick_view .tf-product-info-title .product-card-badge {
        position: static;
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        color: var(--text);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    #quick_view .tf-product-info-title .product-card-badge.badge-offer {
        background: rgba(208, 70, 55, 0.1);
        color: #d04637;
    }

    #quick_view .tf-product-info-title .product-card-badge.badge-best-seller {
        background: rgba(34, 139, 82, 0.1);
        color: #228b52;
    }

    #quick_view .tf-product-info-title .product-card-badge.badge-new {
        background: rgba(33, 111, 219, 0.1);
        color: #216fdb;
    }

    #quick_view .product-title-fit-drop {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    #quick_view .product-title-fit-drop-badge {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 5px 11px;
        border: 1px solid #dedede;
        border-radius: 999px;
        background: #f7f7f7;
        color: #222;
        font-size: 11px;
        line-height: 1;
        white-space: nowrap;
    }

    #quick_view .product-title-fit-drop-label {
        margin-inline-end: 4px;
        color: #777;
        font-weight: 500;
    }

    #quick_view .product-title-fit-drop-value {
        font-weight: 700;
    }
</style>

<div class="modal fade modalDemo popup-quickview" id="quick_view">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="header">
                <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
            </div>
            <div class="wrap">
                <div class="tf-product-media-wrap">
                    <div class="swiper tf-single-slide">
                        <div class="swiper-wrapper" data-qv-gallery>
                            <div class="swiper-slide">
                                <div class="item">
                                    <img src="{{ asset('images/products/4black3.jpg') }}" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="swiper-button-next button-style-arrow single-slide-prev"></div>
                        <div class="swiper-button-prev button-style-arrow single-slide-next"></div>
                    </div>
                </div>
                <div class="tf-product-info-wrap position-relative">
                    <div class="tf-zoom-main"></div>
                    <div class="tf-product-info-list other-image-zoom">
                        <div class="tf-product-info-title">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h5 class="mb-0">
                                    <a class="link" href="{{ route('front.products.show', 'placeholder-product') }}" data-qv-title>{{ __('front.products.placeholder_title') }}</a>
                                </h5>
                                <span class="product-card-badge d-none" data-qv-badge data-badge-class=""></span>
                            </div>

                            <div class="product-title-fit-drop d-none" data-qv-fit-drop-wrap aria-label="{{ app()->getLocale() === 'ar' ? 'قصة الجسم والدروب' : 'Body fit and drop' }}">
                                <span class="product-title-fit-drop-badge d-none" data-qv-body-fit-wrap>
                                    <span class="product-title-fit-drop-label">{{ app()->getLocale() === 'ar' ? 'قصة الجسم' : 'Body Fit' }}:</span>
                                    <span class="product-title-fit-drop-value" data-qv-body-fit>—</span>
                                </span>
                                <span class="product-title-fit-drop-badge d-none" data-qv-drop-wrap>
                                    <span class="product-title-fit-drop-label">{{ app()->getLocale() === 'ar' ? 'الدروب' : 'Drop' }}:</span>
                                    <span class="product-title-fit-drop-value" data-qv-drop-type>—</span>
                                </span>
                            </div>
                        </div>
                        <div class="tf-product-info-price">
                            <div class="tf-product-price-wrap">
                                <div class="price price-on-sale js-currency-price" data-qv-price-current data-base-price="850000" data-base-currency="SYP">850,000 SYP</div>
                                <del class="compare-at-price js-currency-price d-none" data-qv-price-old data-base-price="0" data-base-currency="SYP"></del>
                            </div>
                        </div>
                        <div class="tf-product-info-liveview">
                            <p>{{ __('front.products.product_code') }}: <span class="fw-6" dir="ltr" data-qv-product-code>---</span></p>
                        </div>
                        <div class="tf-product-info-variant-picker">
                            <div class="variant-picker-item">
                                <div class="variant-picker-label">
                                    {{ __('front.products.color') }}: <span class="fw-6 variant-picker-label-value" data-qv-color-label>{{ __('front.products.placeholder_color') }}</span>
                                </div>
                                <div class="variant-picker-values" data-qv-colors></div>
                            </div>
                            <div class="variant-picker-item">
                                <div class="variant-picker-label">
                                    {{ __('front.products.size') }}: <span class="fw-6 variant-picker-label-value" data-qv-size-label>{{ __('front.products.placeholder_size') }}</span>
                                </div>
                                <div class="variant-picker-values" data-qv-sizes></div>
                            </div>
                        </div>
                        <div class="tf-product-info-quantity">
                            <div class="quantity-title fw-6">{{ __('front.products.quantity') }}</div>
                            <div class="wg-quantity">
                                <span class="btn-quantity minus-btn">-</span>
                                <input type="text" name="number" value="1" data-qv-qty>
                                <span class="btn-quantity plus-btn">+</span>
                            </div>
                        </div>
                        <div class="tf-product-info-buy-button">
                            <form class="">
                                <a href="#" class="tf-btn btn-fill justify-content-center fw-6 fs-16 flex-grow-1 animate-hover-btn btn-add-to-cart" data-cart-submit data-cart-url="">
                                    <span>{{ __('front.products.add_to_cart') }} -&nbsp;</span><span class="tf-qty-price js-currency-price" data-qv-submit-price data-base-price="850000" data-base-currency="SYP">850,000 SYP</span>
                                </a>
                                <a href="javascript:void(0);" class="tf-product-btn-wishlist hover-tooltip box-icon bg_white wishlist btn-icon-action" data-wishlist-button>
                                    <span class="icon icon-heart"></span>
                                    <span class="tooltip">{{ __('front.products.add_to_wishlist') }}</span>
                                    <span class="icon icon-delete"></span>
                                </a>
                            </form>
                        </div>
                        <div>
                            <a href="{{ route('front.products.show', 'placeholder-product') }}" class="tf-btn fw-6 btn-line" data-qv-detail>{{ __('front.products.view_full_details') }}<i class="icon icon-arrow1-top-left"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
