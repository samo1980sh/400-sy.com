@php
    $cartState = $cartState ?? [];
    $items = collect($cartState['items'] ?? []);
    $count = (int) ($cartState['count'] ?? $items->count());
    $currency = $cartState['currency'] ?? (session('selectedCurrency') ?? 'SYP');
    $subtotal = (int) ($cartState['subtotal'] ?? 0);
    $subtotalLabel = $cartState['subtotal_label'] ?? (number_format($subtotal, 0) . ' ' . $currency);
@endphp

<div data-cart-page-content>
    @if ($items->isNotEmpty())
        <div class="row g-4 align-items-start">
            <div class="col-xl-8">
                <div class="front-cart-items">
                    <div class="front-cart-head d-none d-lg-grid">
                        <div>{{ __('front.cart.product') }}</div>
                        <div class="text-center">{{ __('front.cart.unit_price') }}</div>
                        <div class="text-center">{{ __('front.cart.quantity') }}</div>
                        <div class="text-end">{{ __('front.cart.total') }}</div>
                    </div>

                    @foreach ($items as $item)
                        @php
                            $unitPrice = (int) ($item['unit_price'] ?? $item['base_price'] ?? 0);
                            $lineTotal = (int) ($item['line_total'] ?? ($unitPrice * (int) ($item['qty'] ?? 1)));
                            $baseCurrency = $item['base_currency'] ?? $currency;
                        @endphp

                        <div
                            class="front-cart-item"
                            data-cart-page-item
                            data-cart-key="{{ $item['key'] ?? '' }}"
                            data-cart-update-url="{{ $item['update_url'] ?? '' }}"
                            data-cart-remove-url="{{ $item['remove_url'] ?? '' }}"
                        >
                            <div class="front-cart-product">
                                <a href="{{ $item['url'] ?? '#' }}" class="front-cart-image">
                                    <img src="{{ $item['image'] ?? '' }}" alt="{{ $item['title'] ?? '' }}" loading="lazy">
                                </a>

                                <div class="front-cart-product-info">
                                    <a href="{{ $item['url'] ?? '#' }}" class="title link fw-6">
                                        {{ $item['title'] ?? '' }}
                                    </a>

                                    @if (! empty($item['meta_variant']))
                                        <div class="front-cart-variant d-flex align-items-center flex-wrap gap-2">
                                            @if (! empty($item['color_swatch_style']))
                                                <span class="cart-color-swatch" style="{!! $item['color_swatch_style'] !!}" aria-hidden="true"></span>
                                            @endif
                                            <span>{{ $item['meta_variant'] }}</span>
                                        </div>
                                    @endif

                                    <button
                                        type="button"
                                        class="front-cart-remove link"
                                        data-cart-page-remove
                                        aria-label="{{ __('front.cart.remove_item') }}"
                                    >
                                        {{ __('front.cart.remove') }}
                                    </button>
                                </div>
                            </div>

                            <div class="front-cart-unit-price text-lg-center">
                                <span class="front-cart-mobile-label d-lg-none">{{ __('front.cart.unit_price') }}</span>
                                <span
                                    class="fw-6 js-currency-price"
                                    data-base-price="{{ $unitPrice }}"
                                    data-base-currency="{{ $baseCurrency }}"
                                >
                                    {{ $item['unit_price_label'] ?? $item['price_label'] ?? '' }}
                                </span>
                            </div>

                            <div class="front-cart-quantity text-lg-center">
                                <span class="front-cart-mobile-label d-lg-none">{{ __('front.cart.quantity') }}</span>
                                <div class="wg-quantity small mx-lg-auto">
                                    <button type="button" class="btn-quantity" data-cart-page-qty="decrease" aria-label="-">−</button>
                                    <input
                                        type="number"
                                        min="1"
                                        max="99"
                                        value="{{ $item['qty'] ?? 1 }}"
                                        data-cart-page-quantity
                                        aria-label="{{ __('front.cart.quantity_label') }}"
                                    >
                                    <button type="button" class="btn-quantity" data-cart-page-qty="increase" aria-label="+">+</button>
                                </div>
                            </div>

                            <div class="front-cart-line-total text-lg-end">
                                <span class="front-cart-mobile-label d-lg-none">{{ __('front.cart.total') }}</span>
                                <span
                                    class="fw-6 js-currency-price"
                                    data-base-price="{{ $lineTotal }}"
                                    data-base-currency="{{ $baseCurrency }}"
                                >
                                    {{ $item['line_total_label'] ?? (number_format($lineTotal, 0) . ' ' . $baseCurrency) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-xl-4">
                <div class="tf-page-cart-footer front-cart-summary">
                    <h5 class="mb_20">{{ __('front.cart.order_summary') }}</h5>

                    <div class="tf-cart-totals-discounts">
                        <div class="tf-cart-total">{{ __('front.cart.items_count') }}</div>
                        <div class="fw-6" data-cart-page-count>{{ $count }} {{ __('front.cart.line_items') }}</div>
                    </div>

                    <div class="tf-cart-totals-discounts mt_16">
                        <div class="tf-cart-total">{{ __('front.cart.subtotal') }}</div>
                        <div
                            class="tf-totals-total-value fw-6 js-currency-price"
                            data-cart-page-subtotal
                            data-base-price="{{ $subtotal }}"
                            data-base-currency="{{ $currency }}"
                        >
                            {{ $subtotalLabel }}
                        </div>
                    </div>

                    <div class="tf-mini-cart-line"></div>

                    <p class="text_black-2 mb_18">{{ __('front.cart.shipping_note') }}</p>

                    <div class="tf-cart-checkbox mb_12">
                        <div class="tf-checkbox-wrapp">
                            <input type="checkbox" id="CartPage-Form_agree" data-cart-terms>
                            <div><i class="icon-check"></i></div>
                        </div>
                        <label for="CartPage-Form_agree">
                            {{ __('front.cart.agree_prefix') }}
                            <a href="{{ route('front.pages.show', 'terms-and-conditions') }}" class="text-decoration-underline">
                                {{ __('front.cart.terms_and_conditions') }}
                            </a>
                        </label>
                    </div>

                    <p class="text-danger d-none mb_12" data-cart-terms-error>
                        {{ __('front.cart.terms_required') }}
                    </p>

                    <a
                        href="{{ route('front.checkout') }}"
                        class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center is-disabled"
                        data-cart-checkout
                        aria-disabled="true"
                    >
                        <span>{{ __('front.cart.check_out') }}</span>
                    </a>

                    <a href="{{ route('front.products.index') }}" class="tf-btn btn-outline radius-3 w-100 justify-content-center mt_12">
                        {{ __('front.cart.continue_shopping') }}
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="tf-page-cart text-center front-cart-empty">
            <h5 class="mb_12">{{ __('front.cart.empty_title') }}</h5>
            <p class="text_black-2 mb_24">{{ __('front.cart.empty_message') }}</p>
            <a href="{{ route('front.products.index') }}" class="tf-btn btn-fill animate-hover-btn justify-content-center">
                {{ __('front.cart.continue_shopping') }}
            </a>
        </div>
    @endif
</div>
