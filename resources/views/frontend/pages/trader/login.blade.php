@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
@endphp

@section('title', $page_title ?? ($isArabic ? 'دخول التجار' : 'Trader Login'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-auth-wrap {
            padding: 76px 0;
            background:
                radial-gradient(circle at 12% 16%, rgba(185, 134, 25, .10), transparent 28%),
                linear-gradient(180deg, #f8f7f4 0%, #ffffff 100%);
        }

        .trader-auth-shell {
            max-width: 1040px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 460px;
            border-radius: 28px;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(0,0,0,.08);
            box-shadow: 0 24px 70px rgba(0,0,0,.08);
        }

        .trader-auth-hero {
            padding: 46px;
            color: #fff;
            background:
                linear-gradient(135deg, rgba(17,17,17,.97), rgba(61,50,29,.94)),
                radial-gradient(circle at 90% 12%, rgba(185,134,25,.42), transparent 30%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 480px;
        }

        .trader-auth-badge {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            padding: 7px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 20px;
            font-family: inherit;
        }

        .trader-auth-hero h1 {
            margin: 0;
            color: #fff;
            font-family: inherit;
            font-size: clamp(32px, 4vw, 48px);
            line-height: 1.25;
            font-weight: 700;
        }

        .trader-auth-hero p {
            margin: 18px 0 0;
            color: rgba(255,255,255,.78);
            line-height: 1.9;
            font-size: 16px;
        }

        .trader-auth-points {
            margin: 34px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 12px;
            color: rgba(255,255,255,.82);
        }

        .trader-auth-points li {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .trader-auth-points li::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #c99a2e;
            flex: 0 0 8px;
        }

        .trader-auth-card {
            padding: 46px 38px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .trader-auth-card h2 {
            margin: 0 0 8px;
            color: #111;
            font-family: inherit;
            font-size: 28px;
            line-height: 1.35;
            font-weight: 700;
        }

        .trader-auth-card .trader-auth-help {
            color: #666;
            line-height: 1.8;
            margin-bottom: 26px;
        }

        .trader-auth-field { margin-bottom: 18px; }
        .trader-auth-field label { display: block; font-weight: 700; margin-bottom: 8px; color: #222; }
        .trader-auth-field input { width: 100%; min-height: 52px; border: 1px solid #ddd; border-radius: 10px; padding: 10px 15px; background: #fff; font-size: 15px; }
        .trader-auth-field input:focus { outline: none; border-color: #c99a2e; box-shadow: 0 0 0 3px rgba(201,154,46,.14); }
        .trader-auth-error { background: #fff1f1; border: 1px solid #f2b5b5; color: #9d1c1c; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; }
        .trader-auth-submit { margin-top: 8px; min-height: 52px; }
        .trader-auth-note { margin-top: 18px; color: #777; font-size: 13px; line-height: 1.7; }

        @media (max-width: 991px) {
            .trader-auth-shell { grid-template-columns: 1fr; }
            .trader-auth-hero { min-height: auto; padding: 34px; }
            .trader-auth-card { padding: 34px; }
        }

        @media (max-width: 575px) {
            .trader-auth-wrap { padding: 46px 0; }
            .trader-auth-shell { border-radius: 20px; }
            .trader-auth-hero,
            .trader-auth-card { padding: 26px; }
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
            'title' => $page_title ?? ($isArabic ? 'دخول التجار' : 'Trader Login'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-auth-wrap">
            <div class="container">
                <div class="trader-auth-shell">
                    <div class="trader-auth-hero">
                        <div>
                            <div class="trader-auth-badge">{{ $isArabic ? 'بوابة تجار الجملة' : 'Wholesale Trader Portal' }}</div>
                            <h1>{{ $isArabic ? 'مساحة خاصة لتجار 400' : 'A dedicated space for 400 traders' }}</h1>
                            <p>{{ $isArabic ? 'تسجيل دخول مستقل للتجار المعتمدين من الإدارة للوصول إلى منتجات الجملة وطلبات السيريالات في المراحل التالية.' : 'A dedicated login for approved traders to access wholesale products and series-based orders in the next phases.' }}</p>
                        </div>
                        <ul class="trader-auth-points">
                            <li>{{ $isArabic ? 'دخول برقم الحساب أو الجوال' : 'Login with account number or mobile' }}</li>
                            <li>{{ $isArabic ? 'حسابات التجار تُدار من لوحة التحكم' : 'Trader accounts are managed by administration' }}</li>
                            <li>{{ $isArabic ? 'بوابة مستقلة عن حسابات العملاء' : 'Separate from customer accounts' }}</li>
                        </ul>
                    </div>

                    <div class="trader-auth-card">
                        <h2>{{ $isArabic ? 'تسجيل الدخول' : 'Sign in' }}</h2>
                        <div class="trader-auth-help">{{ $isArabic ? 'استخدم رقم الحساب أو رقم الجوال مع كلمة المرور المعتمدة من الإدارة.' : 'Use your account number or mobile with the password assigned by the administration.' }}</div>

                        @if ($errors->any())
                            <div class="trader-auth-error">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('front.trader.login.store') }}">
                            @csrf
                            <div class="trader-auth-field">
                                <label for="trader_login">{{ $isArabic ? 'رقم الحساب أو الجوال' : 'Account number or mobile' }}</label>
                                <input id="trader_login" type="text" name="login" value="{{ old('login') }}" dir="ltr" autocomplete="username" required>
                            </div>

                            <div class="trader-auth-field">
                                <label for="trader_password">{{ $isArabic ? 'كلمة المرور' : 'Password' }}</label>
                                <input id="trader_password" type="password" name="password" autocomplete="current-password" required>
                            </div>

                            <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center trader-auth-submit">
                                {{ $isArabic ? 'تسجيل الدخول' : 'Login' }}
                            </button>

                            <div class="trader-auth-note">
                                {{ $isArabic ? 'لا يمكن إنشاء حساب تاجر من الموقع. يرجى التواصل مع الإدارة لاعتماد الحساب.' : 'Trader accounts cannot be created from the website. Please contact administration to activate access.' }}
                            </div>
                        </form>
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