@extends('frontend.layouts.app')

@section('title', $page_title ?? __('front.account.title'))
@section('meta_description', $page_subtitle ?? __('front.account.subtitle'))

@push('styles')
    <style>
        .front-account-page {
            --account-line: #e8e8e8;
            --account-soft: #f8f8f8;
            --account-accent: #d6a72f;
        }
        .front-account-page .account-layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 28px;
            align-items: start;
        }
        .front-account-page .account-card,
        .front-account-page .account-sidebar {
            border: 1px solid var(--account-line);
            border-radius: 12px;
            background: #fff;
        }
        .front-account-page .account-sidebar {
            position: sticky;
            top: 24px;
            overflow: hidden;
        }
        .front-account-page .account-customer-head {
            padding: 22px;
            background: var(--account-soft);
            border-bottom: 1px solid var(--account-line);
        }
        .front-account-page .account-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #111;
            color: #fff;
            font-size: 20px;
            font-weight: 700;
        }
        .front-account-page .account-nav a,
        .front-account-page .account-nav button {
            display: flex;
            width: 100%;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border: 0;
            border-bottom: 1px solid var(--account-line);
            background: #fff;
            color: #222;
            text-align: start;
            transition: background-color .2s ease, color .2s ease;
        }
        .front-account-page .account-nav a:hover,
        .front-account-page .account-nav a.active,
        .front-account-page .account-nav button:hover {
            background: #111;
            color: #fff;
        }
        .front-account-page .account-card {
            padding: 24px;
        }
        .front-account-page .account-card-title {
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--account-line);
        }
        .front-account-page .account-stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }
        .front-account-page .account-stat {
            padding: 20px;
            border: 1px solid var(--account-line);
            border-radius: 10px;
            background: var(--account-soft);
        }
        .front-account-page .account-stat-value {
            display: block;
            margin-top: 8px;
            font-size: 26px;
            font-weight: 700;
        }
        .front-account-page .account-table-wrap {
            overflow-x: auto;
        }
        .front-account-page .account-table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }
        .front-account-page .account-table th,
        .front-account-page .account-table td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--account-line);
            text-align: start;
            vertical-align: middle;
        }
        .front-account-page .account-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: #f1f1f1;
            font-size: 12px;
            font-weight: 700;
        }
        .front-account-page .account-badge.is-success { background: #e8f7ee; color: #157347; }
        .front-account-page .account-badge.is-warning { background: #fff4db; color: #8a5a00; }
        .front-account-page .account-badge.is-danger { background: #fde8e8; color: #b42318; }
        .front-account-page .account-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .front-account-page .account-form-grid .full-width { grid-column: 1 / -1; }
        .front-account-page .account-label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }
        .front-account-page .account-address-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .front-account-page .account-address-card {
            padding: 20px;
            border: 1px solid var(--account-line);
            border-radius: 10px;
            background: #fff;
        }
        .front-account-page .account-address-card.is-default {
            border-color: #111;
            box-shadow: 0 0 0 1px #111;
        }
        .front-account-page details > summary {
            cursor: pointer;
            font-weight: 700;
        }
        .front-account-page .account-order-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }
        .front-account-page .account-summary-box {
            padding: 16px;
            border-radius: 10px;
            background: var(--account-soft);
        }
        .front-account-page .account-timeline {
            border-inline-start: 2px solid var(--account-line);
            padding-inline-start: 20px;
        }
        .front-account-page .account-timeline-item {
            position: relative;
            padding-bottom: 18px;
        }
        .front-account-page .account-timeline-item::before {
            content: '';
            position: absolute;
            inset-inline-start: -26px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #111;
        }
        @media (max-width: 991.98px) {
            .front-account-page .account-layout { grid-template-columns: 1fr; }
            .front-account-page .account-sidebar { position: static; }
            .front-account-page .account-stat-grid,
            .front-account-page .account-order-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575.98px) {
            .front-account-page .account-stat-grid,
            .front-account-page .account-form-grid,
            .front-account-page .account-address-grid,
            .front-account-page .account-order-summary { grid-template-columns: 1fr; }
            .front-account-page .account-card { padding: 18px; }
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

    <main class="front-account-page">
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? __('front.account.title'),
            'subtitle' => $page_subtitle ?? __('front.account.subtitle'),
            'breadcrumbs' => $breadcrumb_items ?? [],
        ])

        <section class="flat-spacing-2">
            <div class="container">
                @if (session('account_success'))
                    <div class="alert alert-success mb_24">{{ session('account_success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger mb_24">
                        <ul class="mb-0 ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="account-layout">
                    @include('frontend.pages.account.partials.sidebar', ['customer' => $customer])
                    <div class="account-content">
                        @yield('account_content')
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
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
@endsection
