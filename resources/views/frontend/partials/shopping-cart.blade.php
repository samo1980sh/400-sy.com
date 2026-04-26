@php
    $cartState = $cartState ?? [];
    $items = collect($cartState['items'] ?? []);
    $subtotalLabel = $cartState['subtotal_label'] ?? ('0 ' . ($cartState['currency'] ?? (session('selectedCurrency') ?? 'SYP')));
@endphp

<div class="modal fullRight fade modal-shopping-cart" id="shoppingCart">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="header">
                <div class="title fw-5">{{ __('front.cart.title') }}</div>
                <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
            </div>
            <div class="wrap">
                <div class="tf-mini-cart-wrap">
                    <div class="tf-mini-cart-main">
                        <div class="tf-mini-cart-sroll">
                            <div class="tf-mini-cart-items" data-cart-items>
                                @forelse ($items as $product)
                                    <div class="tf-mini-cart-item" data-cart-key="{{ $product['key'] ?? '' }}" data-cart-update-url="{{ $product['update_url'] ?? '' }}" data-cart-remove-url="{{ $product['remove_url'] ?? '' }}" data-base-price="{{ $product['unit_price'] ?? $product['base_price'] ?? 0 }}" data-unit-price="{{ $product['unit_price'] ?? $product['base_price'] ?? 0 }}">
                                        <div class="tf-mini-cart-image">
                                            <a href="{{ $product['url'] ?? '#' }}">
                                                <img src="{{ $product['image'] ?? '' }}" alt="">
                                            </a>
                                        </div>
                                        <div class="tf-mini-cart-info">
                                            <a class="title link" href="{{ $product['url'] ?? '#' }}">{{ $product['title'] ?? '' }}</a>
                                            <div class="meta-variant">{{ $product['meta_variant'] ?? '' }}</div>
                                            <div class="price fw-6 js-currency-price" data-base-price="{{ $product['unit_price'] ?? $product['base_price'] ?? 0 }}" data-base-currency="{{ $product['base_currency'] ?? 'SYP' }}">{{ $product['unit_price_label'] ?? $product['price_label'] ?? '' }}</div>
                                            <div class="tf-mini-cart-btns">
                                                <div class="wg-quantity small">
                                                    <span class="btn-quantity minus-btn" data-cart-qty="decrease">-</span>
                                                    <input type="text" name="number" value="{{ $product['qty'] ?? 1 }}">
                                                    <span class="btn-quantity plus-btn" data-cart-qty="increase">+</span>
                                                </div>
                                                <div class="tf-mini-cart-remove" role="button" data-cart-remove>{{ __('front.cart.remove') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="tf-mini-cart-empty" data-cart-empty>
                                        <p>{{ __('front.cart.empty') }}</p>
                                        <a href="{{ route('front.home') }}#featured-products" class="tf-btn btn-line">{{ __('front.cart.continue_shopping') }}</a>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="tf-mini-cart-bottom">
                        <div class="tf-mini-cart-tool">
                            <div class="tf-mini-cart-tool-btn btn-add-note">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="18" viewBox="0 0 16 18" fill="currentColor">
                                    <path d="M5.12187 16.4582H2.78952C2.02045 16.4582 1.39476 15.8325 1.39476 15.0634V2.78952C1.39476 2.02045 2.02045 1.39476 2.78952 1.39476H11.3634C12.1325 1.39476 12.7582 2.02045 12.7582 2.78952V7.07841C12.7582 7.46357 13.0704 7.77579 13.4556 7.77579C13.8407 7.77579 14.1529 7.46357 14.1529 7.07841V2.78952C14.1529 1.25138 12.9016 0 11.3634 0H2.78952C1.25138 0 0 1.25138 0 2.78952V15.0634C0 16.6015 1.25138 17.8529 2.78952 17.8529H5.12187C5.50703 17.8529 5.81925 17.5407 5.81925 17.1555C5.81925 16.7704 5.50703 16.4582 5.12187 16.4582Z"></path>
                                    <path d="M15.3882 10.0971C14.5724 9.28136 13.2452 9.28132 12.43 10.0965L8.60127 13.9168C8.51997 13.9979 8.45997 14.0979 8.42658 14.2078L7.59276 16.9528C7.55646 17.0723 7.55292 17.1993 7.58249 17.3207C7.61206 17.442 7.67367 17.5531 7.76087 17.6425C7.84807 17.7319 7.95768 17.7962 8.07823 17.8288C8.19879 17.8613 8.32587 17.8609 8.44621 17.8276L11.261 17.0479C11.3769 17.0158 11.4824 16.9543 11.5675 16.8694L15.3882 13.0559C16.2039 12.2401 16.2039 10.9129 15.3882 10.0971ZM10.712 15.7527L9.29586 16.145L9.71028 14.7806L12.2937 12.2029L13.2801 13.1893L10.712 15.7527ZM14.4025 12.0692L14.2673 12.204L13.2811 11.2178L13.4157 11.0834C13.6876 10.8115 14.1301 10.8115 14.402 11.0834C14.6739 11.3553 14.6739 11.7977 14.4025 12.0692Z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="tf-mini-cart-bottom-wrap">
                            <div class="tf-cart-totals-discounts">
                                <div class="tf-cart-total">{{ __('front.cart.subtotal') }}</div>
                                <div class="tf-totals-total-value fw-6 js-currency-price" data-cart-subtotal data-base-price="{{ $cartState['subtotal'] ?? 0 }}" data-base-currency="{{ $cartState['currency'] ?? 'SYP' }}">{{ $subtotalLabel }}</div>
                            </div>
                            <div class="tf-mini-cart-line"></div>
                            <div class="tf-cart-checkbox">
                                <div class="tf-checkbox-wrapp">
                                    <input class="" type="checkbox" id="CartDrawer-Form_agree" name="agree_checkbox">
                                    <div>
                                        <i class="icon-check"></i>
                                    </div>
                                </div>
                                <label for="CartDrawer-Form_agree">
                                    {{ __('front.cart.agree_prefix') }}
                                    <a href="{{ route('front.pages.show', 'terms-and-conditions') }}" title="{{ __('front.cart.terms_and_conditions') }}">{{ __('front.cart.terms_and_conditions') }}</a>
                                </label>
                            </div>
                            <div class="tf-mini-cart-view-checkout">
                                <a href="{{ route('front.cart.view') }}" class="tf-btn btn-outline radius-3 link w-100 justify-content-center">{{ __('front.cart.view_cart') }}</a>
                                <a href="{{ route('front.checkout') }}" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center"><span>{{ __('front.cart.check_out') }}</span></a>
                            </div>
                        </div>
                        <div class="tf-mini-cart-tool-openable add-note">
                            <div class="overplay tf-mini-cart-tool-close"></div>
                            <div class="tf-mini-cart-tool-content">
                                <label for="Cart-note" class="tf-mini-cart-tool-text">
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="18" viewBox="0 0 16 18" fill="currentColor">
                                            <path d="M5.12187 16.4582H2.78952C2.02045 16.4582 1.39476 15.8325 1.39476 15.0634V2.78952C1.39476 2.02045 2.02045 1.39476 2.78952 1.39476H11.3634C12.1325 1.39476 12.7582 2.02045 12.7582 2.78952V7.07841C12.7582 7.46357 13.0704 7.77579 13.4556 7.77579C13.8407 7.77579 14.1529 7.46357 14.1529 7.07841V2.78952C14.1529 1.25138 12.9016 0 11.3634 0H2.78952C1.25138 0 0 1.25138 0 2.78952V15.0634C0 16.6015 1.25138 17.8529 2.78952 17.8529H5.12187C5.50703 17.8529 5.81925 17.5407 5.81925 17.1555C5.81925 16.7704 5.50703 16.4582 5.12187 16.4582Z"></path>
                                            <path d="M15.3882 10.0971C14.5724 9.28136 13.2452 9.28132 12.43 10.0965L8.60127 13.9168C8.51997 13.9979 8.45997 14.0979 8.42658 14.2078L7.59276 16.9528C7.55646 17.0723 7.55292 17.1993 7.58249 17.3207C7.61206 17.442 7.67367 17.5531 7.76087 17.6425C7.84807 17.7319 7.95768 17.7962 8.07823 17.8288C8.19879 17.8613 8.32587 17.8609 8.44621 17.8276L11.261 17.0479C11.3769 17.0158 11.4824 16.9543 11.5675 16.8694L15.3882 13.0559C16.2039 12.2401 16.2039 10.9129 15.3882 10.0971ZM10.712 15.7527L9.29586 16.145L9.71028 14.7806L12.2937 12.2029L13.2801 13.1893L10.712 15.7527ZM14.4025 12.0692L14.2673 12.204L13.2811 11.2178L13.4157 11.0834C13.6876 10.8115 14.1301 10.8115 14.402 11.0834C14.6739 11.3553 14.6739 11.7977 14.4025 12.0692Z"></path>
                                        </svg>
                                    </div>
                                    <span>{{ __('front.cart.add_order_note') }}</span>
                                </label>
                                <textarea name="note" id="Cart-note" placeholder="{{ __('front.cart.how_can_we_help_you') }}"></textarea>
                                <div class="tf-cart-tool-btns justify-content-center">
                                    <div class="tf-mini-cart-tool-primary text-center w-100 fw-6 tf-mini-cart-tool-close">{{ __('front.cart.close') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
