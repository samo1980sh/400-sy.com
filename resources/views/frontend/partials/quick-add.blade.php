<style>
    #quick_add .tf-product-info-title .product-card-badge {
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

    #quick_add .tf-product-info-title .product-card-badge.badge-offer {
        background: rgba(208, 70, 55, 0.1);
        color: #d04637;
    }

    #quick_add .tf-product-info-title .product-card-badge.badge-best-seller {
        background: rgba(34, 139, 82, 0.1);
        color: #228b52;
    }

    #quick_add .tf-product-info-title .product-card-badge.badge-new {
        background: rgba(33, 111, 219, 0.1);
        color: #216fdb;
    }

    #quick_add .product-title-fit-drop {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    #quick_add .product-title-fit-drop-badge {
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

    #quick_add .product-title-fit-drop-label {
        margin-inline-end: 4px;
        color: #777;
        font-weight: 500;
    }

    #quick_add .product-title-fit-drop-value {
        font-weight: 700;
    }
</style>

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
                        <div class="tf-product-info-title">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h5 class="mb-0">
                                    <a href="{{ route('front.products.show', 'placeholder-product') }}" data-qadd-title-link>{{ __('front.products.placeholder_title') }}</a>
                                </h5>
                                <span class="product-card-badge d-none" data-qadd-badge data-badge-class=""></span>
                            </div>
                            <div class="product-title-fit-drop d-none" data-qadd-fit-drop-wrap aria-label="{{ app()->getLocale() === 'ar' ? 'قصة الجسم والدروب' : 'Body fit and drop' }}">
                                <span class="product-title-fit-drop-badge d-none" data-qadd-body-fit-wrap>
                                    <span class="product-title-fit-drop-label">{{ app()->getLocale() === 'ar' ? 'قصة الجسم' : 'Body Fit' }}:</span>
                                    <span class="product-title-fit-drop-value" data-qadd-body-fit>—</span>
                                </span>
                                <span class="product-title-fit-drop-badge d-none" data-qadd-drop-wrap>
                                    <span class="product-title-fit-drop-label">{{ app()->getLocale() === 'ar' ? 'الدروب' : 'Drop' }}:</span>
                                    <span class="product-title-fit-drop-value" data-qadd-drop-type>—</span>
                                </span>
                            </div>
                        </div>
                        <div class="tf-product-info-price">
                            <div class="price price-on-sale js-currency-price" data-qadd-price data-base-price="850000" data-base-currency="SYP">850,000 SYP</div>
                            <del class="compare-at-price js-currency-price d-none" data-qadd-price-old data-base-price="0" data-base-currency="SYP"></del>
                        </div>
                        <div class="tf-product-info-liveview">
                            <p>{{ __('front.products.product_code') }}: <span class="fw-6" dir="ltr" data-qadd-product-code>---</span></p>
                        </div>
                    </div>
                </div>
                <div class="tf-product-info-variant-picker mb_15">
                    <div class="variant-picker-item">
                        <div class="variant-picker-label">
                            {{ __('front.products.color') }}: <span class="fw-6 variant-picker-label-value" data-qadd-color-label>{{ __('front.products.placeholder_color') }}</span>
                        </div>
                        <div class="variant-picker-values" data-qadd-colors></div>
                    </div>
                    <div class="variant-picker-item">
                        <div class="variant-picker-label">
                            {{ __('front.products.size') }}: <span class="fw-6 variant-picker-label-value" data-qadd-size-label>{{ __('front.products.placeholder_size') }}</span>
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
                        <div class="tf-product-btn-wishlist btn-icon-action" data-wishlist-button role="button" tabindex="0" aria-pressed="false">
                            <i class="icon-heart"></i>
                            <i class="icon-delete"></i>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
