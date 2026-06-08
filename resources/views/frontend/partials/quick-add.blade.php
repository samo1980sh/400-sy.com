<div class="modal fade modalDemo popup-quickadd" id="quick_add">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="header">
                <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
            </div>
            <div class="wrap">
                <div class="tf-product-info-item">
                    <div class="image">
                        <img src="{{ asset('images/products/4black3.jpg') }}" alt="" data-qadd-image>
                    </div>
                    <div class="content">
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                            <a href="{{ route('front.products.show', 'placeholder-product') }}" data-qadd-title-link>{{ __('front.products.placeholder_title') }}</a>
                            <span class="tf-product-info-badge d-none" data-qadd-badge></span>
                        </div>
                        <div class="tf-product-info-price">
                            <div class="price js-currency-price" data-qadd-price data-base-price="850000" data-base-currency="SYP">850,000 SYP</div>
                            <del class="compare-at-price js-currency-price d-none" data-qadd-price-old data-base-price="0" data-base-currency="SYP"></del>
                        </div>
                        <div class="tf-product-info-code">
                            <span class="label">{{ __('front.products.product_code') }}:</span>
                            <span class="value" data-qadd-product-code data-base-product-code="">---</span>
                        </div>
                        <div class="tf-product-info-liveview d-none" data-qadd-body-fit-wrap>
                            <p>{{ app()->getLocale() === 'ar' ? 'قصة الجسم' : 'Body Fit' }}: <span class="fw-6" data-qadd-body-fit>—</span></p>
                        </div>
                        <div class="tf-product-info-liveview d-none" data-qadd-drop-wrap>
                            <p>{{ app()->getLocale() === 'ar' ? 'الدروب' : 'Drop' }}: <span class="fw-6" data-qadd-drop-type>—</span></p>
                        </div>
                    </div>
                </div>
                <div class="tf-product-info-variant-picker mb_15">
                    <div class="variant-picker-item">
                        <div class="variant-picker-label">
                            {{ __('front.products.color') }}: <span class="fw-6 variant-picker-label-value" data-qadd-color-label>{{ __('front.products.placeholder_color') }}</span>
                        </div>
                        <div class="tf-product-info-code color-code d-none" aria-hidden="true">
                            <span class="label">{{ __('front.products.color_code') }}:</span>
                            <span class="value" data-qadd-color-code>---</span>
                        </div>
                        <div class="variant-picker-values" data-qadd-colors></div>
                    </div>
                    <div class="variant-picker-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="variant-picker-label">
                                {{ __('front.products.size') }}: <span class="fw-6 variant-picker-label-value" data-qadd-size-label>{{ __('front.products.placeholder_size') }}</span>
                            </div>
                            <button type="button" class="size-chart-pill btn-choose-size d-none" data-qadd-find-size>
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
                        <div class="variant-picker-values" data-qadd-sizes></div>
                    </div>
                </div>
                <div class="tf-product-info-quantity mb_15">
                    <div class="quantity-title fw-6">{{ __('front.products.quantity') }}</div>
                    <div class="wg-quantity">
                        <span class="btn-quantity minus-btn">-</span>
                        <input type="text" name="number" value="1" data-qadd-qty>
                        <span class="btn-quantity plus-btn">+</span>
                    </div>
                </div>
                <div class="tf-product-info-buy-button">
                    <form class="">
                        <a href="#" class="tf-btn btn-fill justify-content-center fw-6 fs-16 flex-grow-1 animate-hover-btn btn-add-to-cart" data-cart-submit data-cart-url="">
                            <span>{{ __('front.products.add_to_cart') }} -&nbsp;</span><span class="tf-qty-price js-currency-price" data-qadd-submit-price data-base-price="850000" data-base-currency="SYP">850,000 SYP</span>
                        </a>
                        <div class="tf-product-btn-wishlist btn-icon-action">
                            <i class="icon-heart"></i>
                            <i class="icon-delete"></i>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
