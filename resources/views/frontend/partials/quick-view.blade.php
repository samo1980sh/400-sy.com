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
                            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                                <h5 class="mb-0">
                                    <a class="link" href="{{ route('front.products.show', 'placeholder-product') }}" data-qv-title>{{ __('front.products.placeholder_title') }}</a>
                                </h5>
                                <span class="tf-product-info-badge d-none" data-qv-badge></span>
                            </div>
                        </div>
                        <div class="tf-product-info-price">
                            <div class="tf-product-price-wrap">
                                <div class="price price-current js-currency-price" data-qv-price-current data-base-price="850000" data-base-currency="SYP">850,000 SYP</div>
                                <del class="compare-at-price js-currency-price d-none" data-qv-price-old data-base-price="0" data-base-currency="SYP"></del>
                            </div>
                        </div>
                        <div class="tf-product-info-code">
                            <span class="label">{{ __('front.products.product_code') }}:</span>
                            <span class="value" data-qv-product-code data-base-product-code="">---</span>
                        </div>
                        <div class="tf-product-info-liveview d-none" data-qv-body-fit-wrap>
                            <p>{{ app()->getLocale() === 'ar' ? 'قصة الجسم' : 'Body Fit' }}: <span class="fw-6" data-qv-body-fit>—</span></p>
                        </div>
                        <div class="tf-product-info-liveview d-none" data-qv-drop-wrap>
                            <p>{{ app()->getLocale() === 'ar' ? 'الدروب' : 'Drop' }}: <span class="fw-6" data-qv-drop-type>—</span></p>
                        </div>
                        <div class="tf-product-info-variant-picker">
                            <div class="variant-picker-item">
                                <div class="variant-picker-label">
                                    {{ __('front.products.color') }}: <span class="fw-6 variant-picker-label-value" data-qv-color-label>{{ __('front.products.placeholder_color') }}</span>
                                </div>
                                <div class="tf-product-info-code color-code d-none" aria-hidden="true">
                                    <span class="label">{{ __('front.products.color_code') }}:</span>
                                    <span class="value" data-qv-color-code>---</span>
                                </div>
                                <div class="variant-picker-values" data-qv-colors></div>
                            </div>
                            <div class="variant-picker-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="variant-picker-label">
                                        {{ __('front.products.size') }}: <span class="fw-6 variant-picker-label-value" data-qv-size-label>{{ __('front.products.placeholder_size') }}</span>
                                    </div>
                                    <button type="button" class="size-chart-pill btn-choose-size" data-qv-find-size>
                                        <span class="size-chart-pill__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 7h16" />
                                                <path d="M4 17h16" />
                                                <path d="M7 4v16" />
                                                <path d="M17 4v16" />
                                            </svg>
                                        </span>
                                        <span class="size-chart-pill__text">
                                            <span class="size-chart-pill__title">{{ __('front.products.size_chart') }}</span>
                                            <span class="size-chart-pill__subtitle">{{ __('front.products.find_your_size') }}</span>
                                        </span>
                                    </button>
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
                                <a href="javascript:void(0);" class="tf-product-btn-wishlist hover-tooltip box-icon bg_white wishlist btn-icon-action">
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
