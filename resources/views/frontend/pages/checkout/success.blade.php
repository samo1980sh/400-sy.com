@extends('frontend.layouts.app')

@section('title', $page_title ?? __('front.checkout.success_title'))
@section('meta_description', $page_subtitle ?? __('front.checkout.success_subtitle'))

@php
    $currency = session('selectedCurrency') ?? 'SYP';
    $paymentMethodName = app()->getLocale() === 'ar'
        ? ($payment_method_record?->name_ar ?: $payment_method_record?->name_en ?: $order->payment_method)
        : ($payment_method_record?->name_en ?: $payment_method_record?->name_ar ?: $order->payment_method);
    $shippingMethodName = app()->getLocale() === 'ar'
        ? ($order->shippingMethod?->name_ar ?: $order->shippingMethod?->name_en ?: $order->shipping_label_snapshot)
        : ($order->shippingMethod?->name_en ?: $order->shippingMethod?->name_ar ?: $order->shipping_label_snapshot);
@endphp

@push('styles')
    <style>
        .checkout-success-page {
            --success-green: #168a45;
            --success-green-dark: #0f6f37;
            --success-soft: #edf8f1;
            --success-line: #e6e9e7;
            --success-ink: #171917;
            --success-muted: #727772;
            --success-surface: #ffffff;
            --success-page: #f8faf8;
        }

        .checkout-success-page .success-shell {
            max-width: 1120px;
            margin: 0 auto;
        }

        .checkout-success-page .success-hero {
            position: relative;
            overflow: hidden;
            padding: 46px 32px 38px;
            border: 1px solid #dceee2;
            border-radius: 20px;
            background:
                radial-gradient(circle at 10% 10%, rgba(22, 138, 69, .10), transparent 32%),
                radial-gradient(circle at 92% 92%, rgba(22, 138, 69, .08), transparent 34%),
                var(--success-surface);
            box-shadow: 0 16px 45px rgba(20, 47, 29, .08);
            text-align: center;
        }

        .checkout-success-page .success-icon-wrap {
            width: 90px;
            height: 90px;
            margin: 0 auto 22px;
            border: 8px solid #f3fbf6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--success-soft);
            color: var(--success-green);
            box-shadow: 0 10px 24px rgba(22, 138, 69, .14);
        }

        .checkout-success-page .success-icon-wrap svg {
            width: 40px;
            height: 40px;
        }

        .checkout-success-page .success-title {
            margin: 0 0 10px;
            color: var(--success-ink);
            font-size: clamp(29px, 4vw, 43px);
            font-weight: 700;
            line-height: 1.2;
        }

        .checkout-success-page .success-lead {
            max-width: 650px;
            margin: 0 auto;
            color: var(--success-muted);
            font-size: 15px;
            line-height: 1.9;
        }

        .checkout-success-page .success-reference {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 8px 12px;
            margin-top: 22px;
            padding: 10px 18px;
            border: 1px dashed #b9d9c4;
            border-radius: 999px;
            background: rgba(255, 255, 255, .85);
            color: var(--success-muted);
        }

        .checkout-success-page .success-reference strong {
            color: var(--success-green-dark);
            font-weight: 700;
            letter-spacing: .02em;
        }

        .checkout-success-page .success-meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 22px;
        }

        .checkout-success-page .success-meta-card,
        .checkout-success-page .success-panel {
            border: 1px solid var(--success-line);
            border-radius: 16px;
            background: var(--success-surface);
            box-shadow: 0 8px 24px rgba(22, 32, 24, .045);
        }

        .checkout-success-page .success-meta-card {
            min-height: 108px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .checkout-success-page .meta-label {
            margin-bottom: 7px;
            color: var(--success-muted);
            font-size: 12px;
            line-height: 1.4;
        }

        .checkout-success-page .meta-value {
            color: var(--success-ink);
            font-size: 14px;
            font-weight: 700;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .checkout-success-page .success-content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(310px, .85fr);
            gap: 20px;
            align-items: start;
            margin-top: 20px;
        }

        .checkout-success-page .success-panel {
            padding: 24px;
        }

        .checkout-success-page .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--success-line);
        }

        .checkout-success-page .panel-title {
            display: flex;
            align-items: center;
            gap: 11px;
            margin: 0;
            color: var(--success-ink);
            font-size: 18px;
            font-weight: 700;
        }

        .checkout-success-page .panel-title-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--success-soft);
            color: var(--success-green);
        }

        .checkout-success-page .panel-title-icon svg {
            width: 19px;
            height: 19px;
        }

        .checkout-success-page .items-count {
            min-width: 30px;
            height: 30px;
            padding: 0 9px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f2f4f2;
            color: var(--success-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .checkout-success-page .ordered-list {
            display: grid;
            gap: 12px;
        }

        .checkout-success-page .ordered-item {
            display: grid;
            grid-template-columns: 52px minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 15px;
            border: 1px solid #ecefec;
            border-radius: 13px;
            background: #fcfdfc;
        }

        .checkout-success-page .ordered-index {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--success-soft);
            color: var(--success-green-dark);
            font-weight: 700;
        }

        .checkout-success-page .ordered-name {
            margin-bottom: 5px;
            color: var(--success-ink);
            font-size: 14px;
            font-weight: 700;
            line-height: 1.6;
        }

        .checkout-success-page .ordered-options {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .checkout-success-page .option-pill,
        .checkout-success-page .qty-pill {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            padding: 3px 9px;
            border-radius: 999px;
            background: #f0f2f0;
            color: #666b67;
            font-size: 11px;
            line-height: 1.3;
        }

        .checkout-success-page .ordered-price {
            min-width: 112px;
            text-align: end;
        }

        .checkout-success-page .ordered-price .price-value {
            color: var(--success-ink);
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }

        .checkout-success-page .ordered-price .qty-pill {
            margin-top: 7px;
            justify-content: center;
        }

        .checkout-success-page .success-sidebar {
            display: grid;
            gap: 20px;
        }

        .checkout-success-page .delivery-person {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--success-line);
        }

        .checkout-success-page .delivery-name {
            margin-bottom: 5px;
            color: var(--success-ink);
            font-size: 15px;
            font-weight: 700;
        }

        .checkout-success-page .delivery-line {
            display: flex;
            gap: 9px;
            align-items: flex-start;
            margin-bottom: 8px;
            color: var(--success-muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .checkout-success-page .delivery-line:last-child {
            margin-bottom: 0;
        }

        .checkout-success-page .delivery-dot {
            width: 7px;
            height: 7px;
            flex: 0 0 7px;
            margin-top: 8px;
            border-radius: 50%;
            background: #9bcbae;
        }

        .checkout-success-page .totals-list {
            display: grid;
            gap: 10px;
        }

        .checkout-success-page .total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            color: var(--success-muted);
            font-size: 13px;
        }

        .checkout-success-page .total-row span:last-child {
            color: var(--success-ink);
            font-weight: 600;
            white-space: nowrap;
        }

        .checkout-success-page .grand-total {
            margin-top: 5px;
            padding-top: 15px;
            border-top: 1px solid var(--success-line);
            color: var(--success-ink);
            font-size: 17px;
            font-weight: 700;
        }

        .checkout-success-page .grand-total span:last-child {
            color: var(--success-green-dark);
            font-size: 19px;
            font-weight: 800;
        }

        .checkout-success-page .success-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 26px;
        }

        .checkout-success-page .success-actions .tf-btn {
            min-width: 190px;
            min-height: 48px;
            border-radius: 8px;
            font-weight: 700;
        }

        @media (max-width: 991.98px) {
            .checkout-success-page .success-meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .checkout-success-page .success-content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .checkout-success-page .success-hero {
                padding: 34px 18px 30px;
                border-radius: 16px;
            }

            .checkout-success-page .success-icon-wrap {
                width: 78px;
                height: 78px;
            }

            .checkout-success-page .success-meta-grid {
                grid-template-columns: 1fr;
            }

            .checkout-success-page .success-meta-card,
            .checkout-success-page .success-panel {
                border-radius: 13px;
            }

            .checkout-success-page .success-panel {
                padding: 18px 15px;
            }

            .checkout-success-page .ordered-item {
                grid-template-columns: 44px minmax(0, 1fr);
                padding: 13px;
            }

            .checkout-success-page .ordered-index {
                width: 44px;
                height: 44px;
            }

            .checkout-success-page .ordered-price {
                grid-column: 1 / -1;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                min-width: 0;
                padding-top: 11px;
                border-top: 1px dashed #e2e6e2;
                text-align: start;
            }

            .checkout-success-page .ordered-price .qty-pill {
                margin-top: 0;
            }

            .checkout-success-page .success-actions {
                flex-direction: column;
            }

            .checkout-success-page .success-actions .tf-btn {
                width: 100%;
                min-width: 0;
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

    <main class="checkout-success-page">
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? __('front.checkout.success_title'),
            'subtitle' => $page_subtitle ?? __('front.checkout.success_subtitle'),
            'breadcrumbs' => $breadcrumb_items ?? [],
        ])

        <section class="flat-spacing-2">
            <div class="container">
                <div class="success-shell">
                    <div class="success-hero">
                        <div class="success-icon-wrap" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"></path>
                            </svg>
                        </div>

                        <h1 class="success-title">{{ __('front.checkout.thank_you') }}</h1>
                        <p class="success-lead">{{ __('front.checkout.success_message') }}</p>

                        <div class="success-reference">
                            <span>{{ __('front.checkout.order_number') }}</span>
                            <strong dir="ltr">{{ $order->order_no }}</strong>
                        </div>
                    </div>

                    <div class="success-meta-grid">
                        <div class="success-meta-card">
                            <div class="meta-label">{{ __('front.checkout.order_date') }}</div>
                            <div class="meta-value" dir="ltr">{{ optional($order->created_at)->format('Y-m-d H:i') }}</div>
                        </div>

                        <div class="success-meta-card">
                            <div class="meta-label">{{ __('front.checkout.payment_method') }}</div>
                            <div class="meta-value">{{ $paymentMethodName ?: '—' }}</div>
                        </div>

                        <div class="success-meta-card">
                            <div class="meta-label">{{ __('front.checkout.shipping_method') }}</div>
                            <div class="meta-value">{{ $shippingMethodName ?: '—' }}</div>
                        </div>

                        <div class="success-meta-card">
                            <div class="meta-label">{{ __('front.checkout.grand_total') }}</div>
                            <div class="meta-value js-currency-price"
                                 data-base-price="{{ (float) $order->total }}"
                                 data-base-currency="{{ $currency }}">
                                {{ number_format((float) $order->total, 0) }} {{ $currency }}
                            </div>
                        </div>
                    </div>

                    <div class="success-content-grid">
                        <section class="success-panel">
                            <div class="panel-heading">
                                <h2 class="panel-title">
                                    <span class="panel-title-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                                            <path d="M3 6h18"></path>
                                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                                        </svg>
                                    </span>
                                    {{ __('front.checkout.ordered_items') }}
                                </h2>
                                <span class="items-count">{{ $order->items->count() }}</span>
                            </div>

                            <div class="ordered-list">
                                @foreach ($order->items as $item)
                                    <article class="ordered-item">
                                        <div class="ordered-index" aria-hidden="true">{{ $loop->iteration }}</div>

                                        <div>
                                            <div class="ordered-name">{{ $item->product_name_snapshot ?: __('front.cart.product') }}</div>

                                            <div class="ordered-options">
                                                @if ($item->color_name_snapshot)
                                                    <span class="option-pill">{{ $item->color_name_snapshot }}</span>
                                                @endif

                                                @if ($item->size_name_snapshot)
                                                    <span class="option-pill">{{ $item->size_name_snapshot }}</span>
                                                @endif

                                                @if (! $item->color_name_snapshot && ! $item->size_name_snapshot)
                                                    <span class="option-pill">—</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="ordered-price">
                                            <div class="price-value js-currency-price"
                                                 data-base-price="{{ (float) $item->line_total }}"
                                                 data-base-currency="{{ $currency }}">
                                                {{ number_format((float) $item->line_total, 0) }} {{ $currency }}
                                            </div>
                                            <span class="qty-pill">{{ __('front.checkout.quantity_short') }}: {{ $item->quantity }}</span>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>

                        <aside class="success-sidebar">
                            <section class="success-panel">
                                <div class="panel-heading">
                                    <h2 class="panel-title">
                                        <span class="panel-title-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                                                <circle cx="12" cy="10" r="3"></circle>
                                            </svg>
                                        </span>
                                        {{ __('front.checkout.delivery_details') }}
                                    </h2>
                                </div>

                                <div class="delivery-person">
                                    <div class="delivery-name">{{ $order->shipping_contact_name_snapshot ?: $order->customer_name_snapshot }}</div>
                                    <div class="delivery-line" dir="ltr">
                                        <span class="delivery-dot" aria-hidden="true"></span>
                                        <span>{{ $order->shipping_mobile_snapshot ?: $order->customer_mobile_snapshot }}</span>
                                    </div>
                                </div>

                                @if (collect([$order->shipping_city_snapshot, $order->shipping_area_snapshot])->filter()->isNotEmpty())
                                    <div class="delivery-line">
                                        <span class="delivery-dot" aria-hidden="true"></span>
                                        <span>{{ collect([$order->shipping_city_snapshot, $order->shipping_area_snapshot])->filter()->implode(' - ') }}</span>
                                    </div>
                                @endif

                                @if ($order->shipping_address_line_snapshot)
                                    <div class="delivery-line">
                                        <span class="delivery-dot" aria-hidden="true"></span>
                                        <span>{{ $order->shipping_address_line_snapshot }}</span>
                                    </div>
                                @endif
                            </section>

                            <section class="success-panel">
                                <div class="panel-heading">
                                    <h2 class="panel-title">
                                        <span class="panel-title-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                                <path d="M2 10h20"></path>
                                            </svg>
                                        </span>
                                        {{ __('front.checkout.grand_total') }}
                                    </h2>
                                </div>

                                <div class="totals-list">
                                    <div class="total-row">
                                        <span>{{ __('front.cart.subtotal') }}</span>
                                        <span class="js-currency-price"
                                              data-base-price="{{ (float) $order->total_before_discount }}"
                                              data-base-currency="{{ $currency }}">
                                            {{ number_format((float) $order->total_before_discount, 0) }} {{ $currency }}
                                        </span>
                                    </div>

                                    <div class="total-row">
                                        <span>{{ __('front.checkout.shipping_cost') }}</span>
                                        <span class="js-currency-price"
                                              data-base-price="{{ (float) $order->shipping_cost }}"
                                              data-base-currency="{{ $currency }}">
                                            {{ number_format((float) $order->shipping_cost, 0) }} {{ $currency }}
                                        </span>
                                    </div>

                                    <div class="total-row grand-total">
                                        <span>{{ __('front.checkout.grand_total') }}</span>
                                        <span class="js-currency-price"
                                              data-base-price="{{ (float) $order->total }}"
                                              data-base-currency="{{ $currency }}">
                                            {{ number_format((float) $order->total, 0) }} {{ $currency }}
                                        </span>
                                    </div>
                                </div>
                            </section>
                        </aside>
                    </div>

                    <div class="success-actions">
                        <a href="{{ route('front.products.index') }}" class="tf-btn btn-fill animate-hover-btn justify-content-center">
                            {{ __('front.checkout.continue_shopping') }}
                        </a>
                        <a href="{{ route('front.home') }}" class="tf-btn btn-outline justify-content-center">
                            {{ __('front.nav.home') }}
                        </a>
                    </div>
                </div>
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
    @include('frontend.partials.shopping-cart', ['cartState' => ['items' => [], 'count' => 0, 'subtotal' => 0, 'currency' => $currency]])
    @include('frontend.partials.auth-modals')
@endsection
