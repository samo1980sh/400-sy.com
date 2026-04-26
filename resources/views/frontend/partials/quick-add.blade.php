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
                        <a href="{{ route('front.products.show', 'placeholder-product') }}" data-qadd-title-link>{{ __('front.products.placeholder_title') }}</a>
                        <div class="tf-product-info-price">
                            <div class="price js-currency-price" data-qadd-price data-base-price="850000" data-base-currency="SYP">850,000 SYP</div>
                            <del class="compare-at-price js-currency-price d-none" data-qadd-price-old data-base-price="0" data-base-currency="SYP"></del>
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
