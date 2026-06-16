@extends('frontend.layouts.app')

@section('title', $page_title ?? __('front.cart.page_title'))
@section('meta_description', $page_subtitle ?? __('front.cart.page_subtitle'))

@push('styles')
    <style>
        .front-cart-page .front-cart-items,
        .front-cart-page .front-cart-summary {
            border: 1px solid var(--line, #e9e9e9);
            border-radius: 8px;
            background: #fff;
        }

        .front-cart-page .front-cart-head,
        .front-cart-page .front-cart-item {
            grid-template-columns: minmax(0, 2fr) minmax(120px, .75fr) minmax(150px, .85fr) minmax(120px, .75fr);
            gap: 20px;
            align-items: center;
        }

        .front-cart-page .front-cart-head {
            display: grid;
            padding: 18px 20px;
            border-bottom: 1px solid var(--line, #e9e9e9);
            font-weight: 600;
        }

        .front-cart-page .front-cart-item {
            display: grid;
            padding: 20px;
            border-bottom: 1px solid var(--line, #e9e9e9);
            transition: opacity .2s ease;
        }

        .front-cart-page .front-cart-item:last-child {
            border-bottom: 0;
        }

        .front-cart-page .front-cart-item.is-loading {
            opacity: .55;
            pointer-events: none;
        }

        .front-cart-page .front-cart-product {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .front-cart-page .front-cart-image {
            width: 96px;
            min-width: 96px;
            aspect-ratio: 3 / 4;
            overflow: hidden;
            border-radius: 6px;
            background: #f7f7f7;
        }

        .front-cart-page .front-cart-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .front-cart-page .front-cart-product-info {
            min-width: 0;
        }

        .front-cart-page .front-cart-product-info .title {
            display: inline-block;
            margin-bottom: 8px;
        }

        .front-cart-page .front-cart-variant {
            color: var(--secondary, #757575);
            font-size: 14px;
        }

        .front-cart-page .cart-color-swatch {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, .16);
            display: inline-flex;
            flex: 0 0 16px;
        }

        .front-cart-page .front-cart-remove {
            border: 0;
            padding: 0;
            margin-top: 10px;
            background: transparent;
            color: var(--secondary, #757575);
            text-decoration: underline;
            cursor: pointer;
        }

        .front-cart-page .wg-quantity {
            display: inline-flex;
        }

        .front-cart-page .wg-quantity button {
            border: 0;
            background: transparent;
        }

        .front-cart-page .wg-quantity input[type="number"] {
            -moz-appearance: textfield;
        }

        .front-cart-page .wg-quantity input[type="number"]::-webkit-outer-spin-button,
        .front-cart-page .wg-quantity input[type="number"]::-webkit-inner-spin-button {
            margin: 0;
            -webkit-appearance: none;
        }

        .front-cart-page .front-cart-summary {
            padding: 24px;
            position: sticky;
            top: 24px;
        }

        .front-cart-page .front-cart-empty {
            padding: 64px 20px;
            border: 1px solid var(--line, #e9e9e9);
            border-radius: 8px;
        }

        .front-cart-page [data-cart-checkout].is-disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .front-cart-page .front-cart-mobile-label {
            color: var(--secondary, #757575);
            font-size: 13px;
        }

        @media (max-width: 991.98px) {
            .front-cart-page .front-cart-item {
                grid-template-columns: 1fr 1fr;
            }

            .front-cart-page .front-cart-product {
                grid-column: 1 / -1;
            }

            .front-cart-page .front-cart-unit-price,
            .front-cart-page .front-cart-quantity,
            .front-cart-page .front-cart-line-total {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
            }

            .front-cart-page .front-cart-line-total {
                grid-column: 1 / -1;
                border-top: 1px dashed var(--line, #e9e9e9);
                padding-top: 14px;
            }

            .front-cart-page .front-cart-summary {
                position: static;
            }
        }

        @media (max-width: 575.98px) {
            .front-cart-page .front-cart-item {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 14px;
                padding: 16px;
            }

            .front-cart-page .front-cart-image {
                width: 82px;
                min-width: 82px;
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

    <main class="front-cart-page" data-cart-page data-cart-error-message="{{ __('front.cart.update_failed') }}">
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? __('front.cart.page_title'),
            'subtitle' => $page_subtitle ?? __('front.cart.page_subtitle'),
            'breadcrumbs' => $breadcrumb_items ?? [],
        ])

        <section class="flat-spacing-2">
            <div class="container">
                @php
                    $cartPageError = session('cart_error') ?: $errors->first('cart');
                @endphp

                <div
                    class="alert alert-danger {{ filled($cartPageError) ? '' : 'd-none' }} mb_20"
                    role="alert"
                    data-cart-page-error
                >
                    {{ $cartPageError ?: __('front.cart.update_failed') }}
                </div>

                @include('frontend.partials.cart-page-content', ['cartState' => $cart_state ?? []])
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
    <script src="{{ asset('js/frontend-cart-page.js') }}?v={{ filemtime(public_path('js/frontend-cart-page.js')) }}"></script>
@endpush
