@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $groupName = $trader->wholesaleCustomerGroup
        ? ($isArabic ? ($trader->wholesaleCustomerGroup->name_ar ?? $trader->wholesaleCustomerGroup->name_en ?? '-') : ($trader->wholesaleCustomerGroup->name_en ?? $trader->wholesaleCustomerGroup->name_ar ?? '-'))
        : '-';
@endphp

@section('title', $page_title ?? ($isArabic ? 'لوحة التاجر' : 'Trader Dashboard'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-dashboard-wrap {
            padding: 72px 0;
            background:
                radial-gradient(circle at 14% 20%, rgba(185, 134, 25, .10), transparent 28%),
                linear-gradient(180deg, #f8f7f4 0%, #ffffff 100%);
        }

        .trader-dashboard-shell {
            border-radius: 30px;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(0,0,0,.08);
            box-shadow: 0 26px 70px rgba(0,0,0,.08);
        }

        .trader-dashboard-hero {
            position: relative;
            padding: 42px;
            color: #fff;
            background:
                linear-gradient(135deg, rgba(17,17,17,.97), rgba(61,50,29,.94)),
                radial-gradient(circle at 90% 10%, rgba(185,134,25,.42), transparent 30%);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 26px;
            align-items: center;
        }

        .trader-dashboard-kicker {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            padding: 7px 13px;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            color: #fff;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .trader-dashboard-hero h2 {
            margin: 0;
            color: #fff;
            font-family: inherit;
            font-size: clamp(30px, 4vw, 50px);
            line-height: 1.25;
            font-weight: 700;
        }

        .trader-dashboard-hero p {
            margin: 14px 0 0;
            color: rgba(255,255,255,.78);
            font-size: 16px;
            line-height: 1.85;
            max-width: 720px;
        }

        .trader-dashboard-account {
            min-width: 260px;
            border-radius: 22px;
            padding: 22px;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.18);
            backdrop-filter: blur(6px);
        }

        .trader-dashboard-account span { display: block; color: rgba(255,255,255,.68); font-size: 13px; margin-bottom: 6px; }
        .trader-dashboard-account strong { display: block; color: #fff; font-size: 24px; letter-spacing: .04em; }
        .trader-dashboard-body { padding: 34px; }

        .trader-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .trader-stat {
            position: relative;
            min-height: 128px;
            background: #fafafa;
            border: 1px solid #ededed;
            border-radius: 20px;
            padding: 22px;
            overflow: hidden;
        }

        .trader-stat::after {
            content: '';
            position: absolute;
            inset: auto -34px -42px auto;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: rgba(185,134,25,.11);
        }

        .trader-stat span { display: block; color: #777; font-size: 13px; margin-bottom: 8px; position: relative; z-index: 1; }
        .trader-stat strong { display: block; font-size: 24px; color: #111; position: relative; z-index: 1; }

        .trader-dashboard-actions {
            margin-top: 28px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .trader-action-card {
            position: relative;
            min-height: 154px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 20px;
            background: #fff;
            padding: 22px;
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .trader-action-card::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: rgba(185,134,25,.55);
        }

        html[dir="rtl"] .trader-action-card::before,
        body.rtl .trader-action-card::before {
            inset: 0 0 0 auto;
        }

        .trader-action-card:hover {
            transform: translateY(-4px);
            border-color: rgba(185,134,25,.38);
            box-shadow: 0 18px 40px rgba(0,0,0,.08);
        }

        .trader-action-card h3 {
            margin: 0 0 8px;
            color: #111;
            font-family: inherit;
            font-size: 20px;
            line-height: 1.45;
            font-weight: 700;
        }

        .trader-action-card p {
            margin: 0;
            color: #666;
            line-height: 1.75;
            font-size: 14px;
        }

        .trader-action-link {
            margin-top: 18px;
            width: fit-content;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 6px 14px;
            border-radius: 999px;
            background: #f3ead8;
            color: #111;
            font-weight: 700;
            font-size: 13px;
        }

        .trader-action-card--featured {
            background: linear-gradient(180deg, #fff 0%, #fbf8f1 100%);
            border-color: rgba(185,134,25,.28);
        }

        .trader-action-card--featured::after {
            content: '';
            position: absolute;
            inset: auto -30px -44px auto;
            width: 118px;
            height: 118px;
            border-radius: 50%;
            background: rgba(185,134,25,.12);
        }

        .trader-dashboard-logout { margin-top: 26px; display: flex; justify-content: flex-end; }
        .trader-dashboard-logout form { margin: 0; }

        @media (max-width: 991px) {
            .trader-dashboard-hero { grid-template-columns: 1fr; }
            .trader-dashboard-account { min-width: 0; }
            .trader-dashboard-grid,
            .trader-dashboard-actions { grid-template-columns: 1fr; }
        }

        @media (max-width: 575px) {
            .trader-dashboard-wrap { padding: 46px 0; }
            .trader-dashboard-hero,
            .trader-dashboard-body { padding: 24px; }
            .trader-dashboard-shell { border-radius: 22px; }
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
    ])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? ($isArabic ? 'لوحة التاجر' : 'Trader Dashboard'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-dashboard-wrap">
            <div class="container">
                <div class="trader-dashboard-shell">
                    <div class="trader-dashboard-hero">
                        <div>
                            <div class="trader-dashboard-kicker">{{ $isArabic ? 'بوابة تجار الجملة' : 'Wholesale Trader Portal' }}</div>
                            <h2>{{ $isArabic ? 'مرحباً، ' : 'Welcome, ' }}{{ $trader->name }}</h2>
                            <p>{{ $isArabic ? 'من هنا ستتمكن من الوصول إلى منتجات الجملة، إنشاء طلبات بالسيريالات، ومتابعة حالة طلباتك بعد تجهيز المراحل التالية.' : 'From here you will access wholesale products, create series-based requests, and track your orders as the next phases are completed.' }}</p>
                        </div>

                        <div class="trader-dashboard-account">
                            <span>{{ $isArabic ? 'رقم الحساب' : 'Account number' }}</span>
                            <strong dir="ltr">{{ $trader->account_no }}</strong>
                        </div>
                    </div>

                    <div class="trader-dashboard-body">
                        <div class="trader-dashboard-grid">
                            <div class="trader-stat">
                                <span>{{ $isArabic ? 'مجموعة الجملة' : 'Wholesale group' }}</span>
                                <strong>{{ $groupName }}</strong>
                            </div>
                            <div class="trader-stat">
                                <span>{{ $isArabic ? 'عدد الطلبات' : 'Orders count' }}</span>
                                <strong>{{ $orders_count ?? 0 }}</strong>
                            </div>
                            <div class="trader-stat">
                                <span>{{ $isArabic ? 'حالة الحساب' : 'Account status' }}</span>
                                <strong>{{ $trader->status === 'active' ? ($isArabic ? 'نشط' : 'Active') : ($isArabic ? 'غير نشط' : 'Inactive') }}</strong>
                            </div>
                        </div>

                        <div class="trader-dashboard-actions">
                            <a href="#" class="trader-action-card trader-action-card--featured">
                                <div>
                                    <h3>{{ $isArabic ? 'منتجات الجملة' : 'Wholesale products' }}</h3>
                                    <p>{{ $isArabic ? 'عرض المنتجات والسيريالات المتاحة لحسابك التجاري.' : 'Browse products and series available for your trader group.' }}</p>
                                </div>
                                <span class="trader-action-link">{{ $isArabic ? 'قريباً' : 'Coming soon' }}</span>
                            </a>
                            <a href="#" class="trader-action-card">
                                <div>
                                    <h3>{{ $isArabic ? 'طلباتي' : 'My orders' }}</h3>
                                    <p>{{ $isArabic ? 'متابعة طلبات الجملة وحالاتها بعد الإرسال.' : 'Track your wholesale orders and their current status.' }}</p>
                                </div>
                                <span class="trader-action-link">{{ $isArabic ? 'قريباً' : 'Coming soon' }}</span>
                            </a>
                            <a href="#" class="trader-action-card">
                                <div>
                                    <h3>{{ $isArabic ? 'بيانات الحساب' : 'Account details' }}</h3>
                                    <p>{{ $isArabic ? 'عرض معلومات التاجر ورقم الحساب والمجموعة.' : 'View trader information, account number, and assigned group.' }}</p>
                                </div>
                                <span class="trader-action-link">{{ $isArabic ? 'قريباً' : 'Coming soon' }}</span>
                            </a>
                        </div>

                        <div class="trader-dashboard-logout">
                            <form method="POST" action="{{ route('front.trader.logout') }}">
                                @csrf
                                <button type="submit" class="tf-btn btn-line radius-3">{{ $isArabic ? 'تسجيل الخروج' : 'Logout' }}</button>
                            </form>
                        </div>
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

    @include('frontend.partials.toolbar-bottom', ['cartCount' => $cart_count ?? 0])
    @include('frontend.partials.mobile-menu', ['navCategories' => $nav_categories ?? [], 'quickLinks' => $quick_links ?? []])
    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
@endsection