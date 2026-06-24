@extends('frontend.layouts.app')

@section('title', $page_title ?? __('front.checkout.title'))
@section('meta_description', $page_subtitle ?? __('front.checkout.subtitle'))

@php
    $cartState = $cart_state ?? [];
    $items = collect($cartState['items'] ?? []);
    $currency = $cartState['currency'] ?? (session('selectedCurrency') ?? 'SYP');
    $subtotal = (int) ($cartState['subtotal'] ?? 0);
    $shippingMethods = collect($shipping_methods ?? []);
    $paymentMethods = collect($payment_methods ?? []);
    $requestedShippingId = (int) old('shipping_method_id', (int) ($shippingMethods->first()?->getKey() ?? 0));
    $selectedShippingMethod = $shippingMethods->first(fn ($method): bool => (int) $method->getKey() === $requestedShippingId)
        ?? $shippingMethods->first();
    $selectedShippingId = (int) ($selectedShippingMethod?->getKey() ?? 0);
    $selectedShippingCost = (int) round((float) ($selectedShippingMethod?->cost ?? 0));
    $requestedPaymentCode = (string) old('payment_method', (string) ($paymentMethods->first()?->code ?? ''));
    $selectedPaymentMethod = $paymentMethods->first(fn ($method): bool => (string) $method->code === $requestedPaymentCode)
        ?? $paymentMethods->first();
    $selectedPaymentCode = (string) ($selectedPaymentMethod?->code ?? '');
    $initialTotal = $subtotal + $selectedShippingCost;
    $customer = $authenticated_customer ?? null;
    $couponSystemEnabled = (bool) ($coupon_system_enabled ?? false);
    $savedAddresses = collect($saved_addresses ?? []);
    $defaultAddress = $savedAddresses->firstWhere('is_default', true) ?? $savedAddresses->first();
    $prefillName = old('full_name', $customer?->name ?? $defaultAddress?->contact_name);
    $prefillMobile = old('mobile', $defaultAddress?->mobile ?? $customer?->mobile);
    $prefillEmail = old('email', $customer?->email);
    $prefillCity = old('city', $defaultAddress?->city ?? $customer?->city);
    $prefillArea = old('area', $defaultAddress?->area ?? $customer?->area);
    $prefillAddressLine = old('address_line', $defaultAddress?->address_line);
    $prefillAddressType = old('address_type', $defaultAddress?->address_type ?? 'home');
    $prefillAddressLabel = old('address_label', $defaultAddress?->label);
@endphp

@push('styles')
    <style>
        .front-checkout-page .checkout-card {
            border: 1px solid var(--line, #e9e9e9);
            border-radius: 8px;
            background: #fff;
            padding: 24px;
        }

        .front-checkout-page .checkout-section-title {
            padding-bottom: 14px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--line, #e9e9e9);
        }

        .front-checkout-page .checkout-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .front-checkout-page .checkout-required::after {
            content: ' *';
            color: #dc3545;
        }

        .front-checkout-page .checkout-option {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px;
            border: 1px solid var(--line, #e9e9e9);
            border-radius: 6px;
            cursor: pointer;
            transition: border-color .2s ease, background-color .2s ease;
        }

        .front-checkout-page .checkout-option:has(input:checked) {
            border-color: var(--main, #000);
            background: #fafafa;
        }

        .front-checkout-page .checkout-option input {
            margin-top: 4px;
            flex: 0 0 auto;
        }

        .front-checkout-page .checkout-summary {
            position: sticky;
            top: 24px;
        }

        .front-checkout-page .checkout-item {
            display: grid;
            grid-template-columns: 64px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid var(--line, #e9e9e9);
        }

        .front-checkout-page .checkout-item:first-child {
            padding-top: 0;
        }

        .front-checkout-page .checkout-item-image {
            width: 64px;
            aspect-ratio: 3 / 4;
            overflow: hidden;
            border-radius: 5px;
            background: #f7f7f7;
        }

        .front-checkout-page .checkout-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .front-checkout-page .checkout-total-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 8px 0;
        }

        .front-checkout-page .checkout-grand-total {
            margin-top: 10px;
            padding-top: 18px;
            border-top: 1px solid var(--line, #e9e9e9);
            font-size: 18px;
            font-weight: 700;
        }

        .front-checkout-page .is-submitting {
            opacity: .65;
            pointer-events: none;
        }

        .front-checkout-page .form-control,
        .front-checkout-page .form-select {
            min-height: 48px;
        }

        .front-checkout-page .checkout-coupon-actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
        }

        .front-checkout-page .checkout-coupon-actions .tf-btn {
            min-width: 112px;
            min-height: 48px;
        }

        @media (max-width: 575.98px) {
            .front-checkout-page .checkout-coupon-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 991.98px) {
            .front-checkout-page .checkout-summary {
                position: static;
            }
        }
    </style>
@endpush

@section('content')
    @include('frontend.partials.announcement-bar', [
        'tickerItems' => $ticker_items ?? [],
        'socialLinks' => $social_links ?? [],
    ])

    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'siteName' => $site_name ?? __('front.brand'),
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
    ])

    <main
        class="front-checkout-page"
        data-checkout-page
        data-checkout-currency="{{ $currency }}"
        data-checkout-locale="{{ app()->getLocale() }}"
        data-checkout-coupon-preview-url="{{ route('front.checkout.coupon.preview') }}"
        data-checkout-coupon-error-message="{{ __('front.checkout.coupon_preview_error') }}"
    >
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? __('front.checkout.title'),
            'subtitle' => $page_subtitle ?? __('front.checkout.subtitle'),
            'breadcrumbs' => $breadcrumb_items ?? [],
        ])

        <section class="flat-spacing-2">
            <div class="container">
                @if ($errors->any())
                    <div class="alert alert-danger mb_24" role="alert">
                        <div class="fw-6 mb_8">{{ __('front.checkout.fix_errors') }}</div>
                        <ul class="mb-0 ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @unless ($checkout_available ?? false)
                    <div class="alert alert-warning mb_24" role="alert">
                        {{ __('front.checkout.methods_unavailable') }}
                    </div>
                @endunless

                <form method="POST" action="{{ route('front.checkout.store') }}" data-checkout-form>
                    @csrf

                    <div class="row g-4 align-items-start">
                        <div class="col-lg-7">
                            <div class="checkout-card mb_24">
                                <h5 class="checkout-section-title">{{ __('front.checkout.customer_details') }}</h5>

                                @if ($customer)
                                    <div class="alert alert-light border mb_20">
                                        {{ __('front.checkout.signed_in_as') }} <strong>{{ $customer->name }}</strong>
                                        <span class="ms-2" dir="ltr">{{ $customer->account_no }}</span>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="checkout-full-name" class="checkout-label checkout-required">
                                            {{ __('front.checkout.full_name') }}
                                        </label>
                                        <input
                                            id="checkout-full-name"
                                            type="text"
                                            name="full_name"
                                            value="{{ $prefillName }}"
                                            class="form-control @error('full_name') is-invalid @enderror"
                                            autocomplete="name"
                                            required
                                        >
                                        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="checkout-mobile" class="checkout-label checkout-required">
                                            {{ __('front.checkout.mobile') }}
                                        </label>
                                        <input
                                            id="checkout-mobile"
                                            type="tel"
                                            name="mobile"
                                            value="{{ $prefillMobile }}"
                                            class="form-control @error('mobile') is-invalid @enderror"
                                            autocomplete="tel"
                                            dir="ltr"
                                            required
                                        >
                                        @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="checkout-email" class="checkout-label">
                                            {{ __('front.checkout.email') }}
                                            <span class="text-muted fw-normal">({{ __('front.checkout.optional') }})</span>
                                        </label>
                                        <input
                                            id="checkout-email"
                                            type="email"
                                            name="email"
                                            value="{{ $prefillEmail }}"
                                            class="form-control @error('email') is-invalid @enderror"
                                            autocomplete="email"
                                            dir="ltr"
                                        >
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="checkout-card mb_24">
                                <h5 class="checkout-section-title">{{ __('front.checkout.shipping_address') }}</h5>

                                @if ($customer && $savedAddresses->isNotEmpty())
                                    <div class="mb_20">
                                        <label for="checkout-saved-address" class="checkout-label">{{ __('front.checkout.use_saved_address') }}</label>
                                        <select id="checkout-saved-address" class="form-select" data-checkout-saved-address>
                                            <option value="">{{ __('front.checkout.enter_new_address') }}</option>
                                            @foreach ($savedAddresses as $address)
                                                <option
                                                    value="{{ $address->getKey() }}"
                                                    data-contact-name="{{ $address->contact_name }}"
                                                    data-mobile="{{ $address->mobile }}"
                                                    data-city="{{ $address->city }}"
                                                    data-area="{{ $address->area }}"
                                                    data-address-line="{{ $address->address_line }}"
                                                    data-address-type="{{ $address->address_type }}"
                                                    data-address-label="{{ $address->label }}"
                                                    @selected($defaultAddress && (int) $defaultAddress->getKey() === (int) $address->getKey() && ! old('address_line'))
                                                >
                                                    {{ $address->label ?: __('front.checkout.address_types.' . $address->address_type) }} — {{ $address->city }}، {{ $address->area }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="checkout-city" class="checkout-label checkout-required">{{ __('front.checkout.city') }}</label>
                                        <input
                                            id="checkout-city"
                                            type="text"
                                            name="city"
                                            value="{{ $prefillCity }}"
                                            class="form-control @error('city') is-invalid @enderror"
                                            autocomplete="address-level2"
                                            required
                                        >
                                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="checkout-area" class="checkout-label checkout-required">{{ __('front.checkout.area') }}</label>
                                        <input
                                            id="checkout-area"
                                            type="text"
                                            name="area"
                                            value="{{ $prefillArea }}"
                                            class="form-control @error('area') is-invalid @enderror"
                                            autocomplete="address-level3"
                                            required
                                        >
                                        @error('area')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="checkout-address" class="checkout-label checkout-required">{{ __('front.checkout.address_line') }}</label>
                                        <textarea
                                            id="checkout-address"
                                            name="address_line"
                                            rows="3"
                                            class="form-control @error('address_line') is-invalid @enderror"
                                            autocomplete="street-address"
                                            required
                                        >{{ $prefillAddressLine }}</textarea>
                                        @error('address_line')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="checkout-address-type" class="checkout-label checkout-required">{{ __('front.checkout.address_type') }}</label>
                                        <select
                                            id="checkout-address-type"
                                            name="address_type"
                                            class="form-select @error('address_type') is-invalid @enderror"
                                            required
                                        >
                                            @foreach (['home', 'work', 'other'] as $addressType)
                                                <option value="{{ $addressType }}" @selected($prefillAddressType === $addressType)>
                                                    {{ __('front.checkout.address_types.' . $addressType) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('address_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="checkout-address-label" class="checkout-label">
                                            {{ __('front.checkout.address_label') }}
                                            <span class="text-muted fw-normal">({{ __('front.checkout.optional') }})</span>
                                        </label>
                                        <input
                                            id="checkout-address-label"
                                            type="text"
                                            name="address_label"
                                            value="{{ $prefillAddressLabel }}"
                                            class="form-control @error('address_label') is-invalid @enderror"
                                            placeholder="{{ __('front.checkout.address_label_placeholder') }}"
                                        >
                                        @error('address_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="checkout-card mb_24">
                                <h5 class="checkout-section-title">{{ __('front.checkout.shipping_method') }}</h5>

                                <div class="d-grid gap-3">
                                    @forelse ($shippingMethods as $method)
                                        @php
                                            $methodName = app()->getLocale() === 'ar'
                                                ? ($method->name_ar ?: $method->name_en ?: $method->code)
                                                : ($method->name_en ?: $method->name_ar ?: $method->code);
                                            $methodCost = (int) round((float) $method->cost);
                                        @endphp
                                        <label class="checkout-option">
                                            <input
                                                type="radio"
                                                name="shipping_method_id"
                                                value="{{ $method->getKey() }}"
                                                data-shipping-method
                                                data-shipping-cost="{{ $methodCost }}"
                                                @checked((int) $selectedShippingId === (int) $method->getKey())
                                                required
                                            >
                                            <span class="flex-grow-1">
                                                <span class="d-flex justify-content-between gap-3 fw-6">
                                                    <span>{{ $methodName }}</span>
                                                    <span class="js-currency-price" data-base-price="{{ $methodCost }}" data-base-currency="{{ $currency }}">
                                                        {{ number_format($methodCost, 0) }} {{ $currency }}
                                                    </span>
                                                </span>
                                                @if (filled($method->delivery_time))
                                                    <span class="d-block text-muted mt-1">{{ $method->delivery_time }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @empty
                                        <p class="text-muted mb-0">{{ __('front.checkout.no_shipping_methods') }}</p>
                                    @endforelse
                                </div>
                                @error('shipping_method_id')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                            </div>

                            <div class="checkout-card mb_24">
                                <h5 class="checkout-section-title">{{ __('front.checkout.payment_method') }}</h5>

                                <div class="d-grid gap-3">
                                    @forelse ($paymentMethods as $method)
                                        @php
                                            $methodName = app()->getLocale() === 'ar'
                                                ? ($method->name_ar ?: $method->name_en ?: $method->code)
                                                : ($method->name_en ?: $method->name_ar ?: $method->code);
                                        @endphp
                                        <label class="checkout-option">
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value="{{ $method->code }}"
                                                @checked($selectedPaymentCode === (string) $method->code)
                                                required
                                            >
                                            <span>
                                                <span class="d-block fw-6">{{ $methodName }}</span>
                                                @if (filled($method->notes))
                                                    <span class="d-block text-muted mt-1">{{ $method->notes }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @empty
                                        <p class="text-muted mb-0">{{ __('front.checkout.no_payment_methods') }}</p>
                                    @endforelse
                                </div>
                                @error('payment_method')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                            </div>


                            <div class="checkout-card mb_24">
                                <h5 class="checkout-section-title">هل الطلب هدية؟</h5>

                                <label class="checkout-option">
                                    <input
                                        type="checkbox"
                                        name="is_gift"
                                        value="1"
                                        id="checkout-is-gift"
                                        @checked(old('is_gift'))
                                        data-checkout-gift-toggle
                                    >
                                    <span>
                                        <span class="d-block fw-6">هذا الطلب هدية</span>
                                        <span class="d-block text-muted mt-1">يمكنك إضافة رسالة قصيرة تظهر مع تفاصيل الطلب.</span>
                                    </span>
                                </label>

                                <div class="mt_16 {{ old('is_gift') ? '' : 'd-none' }}" data-checkout-gift-message-wrap>
                                    <label for="checkout-gift-message" class="checkout-label">
                                        رسالة الهدية
                                        <span class="text-muted fw-normal">(اختياري)</span>
                                    </label>
                                    <textarea
                                        id="checkout-gift-message"
                                        name="gift_message"
                                        rows="3"
                                        class="form-control @error('gift_message') is-invalid @enderror"
                                        placeholder="اكتب رسالة قصيرة للهدية"
                                    >{{ old('gift_message') }}</textarea>
                                    @error('gift_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="checkout-card">
                                <label for="checkout-notes" class="checkout-label">
                                    {{ __('front.checkout.notes') }}
                                    <span class="text-muted fw-normal">({{ __('front.checkout.optional') }})</span>
                                </label>
                                <textarea
                                    id="checkout-notes"
                                    name="notes"
                                    rows="4"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    placeholder="{{ __('front.checkout.notes_placeholder') }}"
                                >{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="checkout-card checkout-summary">
                                <h5 class="checkout-section-title">{{ __('front.checkout.order_summary') }}</h5>

                                <div class="mb_20">
                                    @foreach ($items as $item)
                                        @php
                                            $lineTotal = (int) ($item['line_total'] ?? ((int) ($item['unit_price'] ?? 0) * (int) ($item['qty'] ?? 1)));
                                        @endphp
                                        <div class="checkout-item">
                                            <a href="{{ $item['url'] ?? '#' }}" class="checkout-item-image">
                                                <img src="{{ $item['image'] ?? '' }}" alt="{{ $item['title'] ?? '' }}" loading="lazy">
                                            </a>
                                            <div class="min-w-0">
                                                <a href="{{ $item['url'] ?? '#' }}" class="link fw-6 d-block">{{ $item['title'] ?? '' }}</a>
                                                @if (filled($item['meta_variant'] ?? null))
                                                    <small class="text-muted d-block mt-1">{{ $item['meta_variant'] }}</small>
                                                @endif
                                                <small class="text-muted d-block mt-1">{{ __('front.checkout.quantity_short') }}: {{ (int) ($item['qty'] ?? 1) }}</small>
                                            </div>
                                            <div class="text-end fw-6 js-currency-price" data-base-price="{{ $lineTotal }}" data-base-currency="{{ $item['base_currency'] ?? $currency }}">
                                                {{ number_format($lineTotal, 0) }} {{ $item['base_currency'] ?? $currency }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($couponSystemEnabled || $customer)
                                    <div class="border rounded-3 p-3 mb_20">
                                        <div class="fw-6 mb-2">{{ __('front.checkout.coupon_title') }}</div>

                                        @if ($customer)
                                            <p class="text-muted small mb-2">{{ __('front.checkout.discount_code_hint') }}</p>
                                            <div class="checkout-coupon-actions">
                                                <input
                                                    type="text"
                                                    name="coupon_code"
                                                    value="{{ old('coupon_code') }}"
                                                    class="form-control @error('coupon_code') is-invalid @enderror"
                                                    placeholder="{{ __('front.checkout.coupon_placeholder') }}"
                                                    autocomplete="off"
                                                    dir="ltr"
                                                    data-checkout-coupon-input
                                                    data-required-message="{{ __('front.checkout.coupon_required') }}"
                                                >
                                                <button
                                                    type="button"
                                                    class="tf-btn btn-outline animate-hover-btn radius-3 justify-content-center"
                                                    data-checkout-coupon-apply
                                                >
                                                    <span data-checkout-coupon-apply-label>{{ __('front.checkout.apply_coupon') }}</span>
                                                    <span class="d-none" data-checkout-coupon-applying-label>{{ __('front.checkout.applying_coupon') }}</span>
                                                </button>
                                            </div>
                                            @error('coupon_code')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                                            <div class="small mt-2" role="status" aria-live="polite" data-checkout-coupon-feedback></div>
                                            <button
                                                type="button"
                                                class="btn-link link small mt-2 d-none"
                                                data-checkout-coupon-remove
                                            >
                                                {{ __('front.checkout.remove_coupon') }}
                                            </button>
                                        @else
                                            <p class="text-muted mb-0">
                                                {{ __('front.checkout.coupon_login_required') }}
                                                <a href="#login" data-bs-toggle="modal" class="link text-decoration-underline">
                                                    {{ __('front.auth.log_in') }}
                                                </a>
                                            </p>
                                        @endif
                                    </div>
                                @endif

                                <div class="checkout-total-row">
                                    <span>{{ __('front.cart.subtotal') }}</span>
                                    <span class="fw-6 js-currency-price" data-base-price="{{ $subtotal }}" data-base-currency="{{ $currency }}">
                                        {{ number_format($subtotal, 0) }} {{ $currency }}
                                    </span>
                                </div>

                                <div class="checkout-total-row">
                                    <span>{{ __('front.checkout.shipping_cost') }}</span>
                                    <span
                                        class="fw-6 js-currency-price"
                                        data-checkout-shipping-cost
                                        data-base-price="{{ $selectedShippingCost }}"
                                        data-base-currency="{{ $currency }}"
                                    >
                                        {{ number_format($selectedShippingCost, 0) }} {{ $currency }}
                                    </span>
                                </div>

                                <div class="checkout-total-row text-success d-none" data-checkout-coupon-row>
                                    <span>{{ __('front.checkout.coupon_discount') }}</span>
                                    <span class="fw-6" data-checkout-coupon-discount>- {{ number_format(0, 0) }} {{ $currency }}</span>
                                </div>

                                <div class="checkout-total-row checkout-grand-total">
                                    <span>{{ __('front.checkout.grand_total') }}</span>
                                    <span
                                        class="js-currency-price"
                                        data-checkout-total
                                        data-checkout-subtotal="{{ $subtotal }}"
                                        data-base-price="{{ $initialTotal }}"
                                        data-base-currency="{{ $currency }}"
                                    >
                                        {{ number_format($initialTotal, 0) }} {{ $currency }}
                                    </span>
                                </div>

                                <div class="form-check mt_24 mb_20">
                                    <input
                                        class="form-check-input @error('terms') is-invalid @enderror"
                                        type="checkbox"
                                        name="terms"
                                        value="1"
                                        id="checkout-terms"
                                        @checked(old('terms'))
                                        required
                                    >
                                    <label class="form-check-label" for="checkout-terms">
                                        {{ __('front.cart.agree_prefix') }}
                                        <a href="{{ route('front.pages.show', 'terms-and-conditions') }}" class="text-decoration-underline">
                                            {{ __('front.cart.terms_and_conditions') }}
                                        </a>
                                    </label>
                                    @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <button
                                    type="submit"
                                    class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center"
                                    data-checkout-submit
                                    @disabled(! ($checkout_available ?? false))
                                >
                                    <span data-checkout-submit-label>{{ __('front.checkout.place_order') }}</span>
                                    <span class="d-none" data-checkout-submitting-label>{{ __('front.checkout.submitting') }}</span>
                                </button>

                                <a href="{{ route('front.cart.view') }}" class="tf-btn btn-outline radius-3 w-100 justify-content-center mt_12">
                                    {{ __('front.checkout.back_to_cart') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </main>

    @include('frontend.partials.footer', [
        'contact' => $contact ?? null,
        'socialLinks' => $social_links ?? [],
        'footerPages' => $footer_pages ?? [],
        'collections' => $collections ?? [],
    ])

    @include('frontend.partials.toolbar-bottom', [
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
    ])
    @include('frontend.partials.mobile-menu', [
        'navCategories' => $nav_categories ?? [],
        'quickLinks' => $quick_links ?? [],
    ])
    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
@endsection

@push('scripts')
    <script src="{{ asset('js/frontend-checkout.js') }}?v={{ filemtime(public_path('js/frontend-checkout.js')) }}"></script>
    <script>
        document.addEventListener('change', function (event) {
            var toggle = event.target.closest('[data-checkout-gift-toggle]');
            if (!toggle) {
                return;
            }

            var wrapper = document.querySelector('[data-checkout-gift-message-wrap]');
            if (!wrapper) {
                return;
            }

            wrapper.classList.toggle('d-none', !toggle.checked);
        });
        document.addEventListener('change', function (event) {
            var select = event.target.closest('[data-checkout-saved-address]');
            if (!select || !select.selectedOptions.length) {
                return;
            }

            var option = select.selectedOptions[0];
            if (!option.value) {
                ['checkout-city', 'checkout-area', 'checkout-address', 'checkout-address-label'].forEach(function (id) {
                    var field = document.getElementById(id);
                    if (field) {
                        field.value = '';
                    }
                });
                var addressType = document.getElementById('checkout-address-type');
                if (addressType) {
                    addressType.value = 'home';
                }
                return;
            }

            var fields = {
                'checkout-full-name': option.dataset.contactName || '',
                'checkout-mobile': option.dataset.mobile || '',
                'checkout-city': option.dataset.city || '',
                'checkout-area': option.dataset.area || '',
                'checkout-address': option.dataset.addressLine || '',
                'checkout-address-type': option.dataset.addressType || 'home',
                'checkout-address-label': option.dataset.addressLabel || ''
            };

            Object.keys(fields).forEach(function (id) {
                var field = document.getElementById(id);
                if (field) {
                    field.value = fields[id];
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        });
    </script>
@endpush
