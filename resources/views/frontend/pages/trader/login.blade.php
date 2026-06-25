@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
@endphp

@section('title', $page_title ?? ($isArabic ? 'دخول التجار' : 'Trader Login'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-auth { padding: 56px 0 72px; background: #fff; font-family: "Albert Sans", sans-serif; }
        .trader-auth__shell { max-width: 980px; margin: 0 auto; display: grid; grid-template-columns: minmax(0, 1fr) 430px; border: 1px solid #e7e7e7; border-radius: 8px; overflow: hidden; background: #fff; }
        .trader-auth__intro { padding: 40px; background: #111; color: #fff; display: flex; flex-direction: column; justify-content: space-between; gap: 34px; }
        .trader-auth__badge { color: rgba(255,255,255,.72); font-size: 13px; font-weight: 800; margin-bottom: 14px; }
        .trader-auth__intro h1 { margin: 0; color: #fff; font-size: clamp(30px, 4vw, 46px); line-height: 1.25; font-weight: 600; }
        .trader-auth__intro p { margin: 16px 0 0; color: rgba(255,255,255,.76); line-height: 1.9; }
        .trader-auth__points { margin: 0; padding: 0; list-style: none; display: grid; gap: 10px; color: rgba(255,255,255,.84); }
        .trader-auth__points li { display: flex; gap: 10px; align-items: center; }
        .trader-auth__points li::before { content: ""; width: 6px; height: 6px; border-radius: 999px; background: #fff; flex: 0 0 6px; }
        .trader-auth__card { padding: 40px 34px; display: flex; flex-direction: column; justify-content: center; }
        .trader-auth__card h2 { margin: 0 0 8px; color: #111; font-size: 28px; font-weight: 600; }
        .trader-auth__help { color: #666; line-height: 1.8; margin-bottom: 24px; }
        .trader-auth__field { margin-bottom: 18px; }
        .trader-auth__field label { display: block; font-weight: 700; margin-bottom: 8px; color: #222; }
        .trader-auth__field input { width: 100%; min-height: 52px; border: 1px solid #ddd; border-radius: 3px; padding: 10px 14px; background: #fff; color: #111; font-size: 15px; }
        .trader-auth__field input:focus { outline: none; border-color: #111; }
        .trader-auth__error { background: #fff1f1; border: 1px solid #f2b5b5; color: #9d1c1c; border-radius: 3px; padding: 12px 14px; margin-bottom: 18px; }
        .trader-auth__note { margin-top: 18px; color: #777; font-size: 13px; line-height: 1.7; }
        @media (max-width: 991px) {
            .trader-auth__shell { grid-template-columns: 1fr; }
            .trader-auth__intro, .trader-auth__card { padding: 28px; }
        }
    </style>
@endpush

@section('content')
    @include('frontend.pages.trader.partials.header', ['traderCartCount' => $trader_cart_count ?? 0])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? ($isArabic ? 'دخول التجار' : 'Trader Login'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-auth">
            <div class="container">
                <div class="trader-auth__shell">
                    <div class="trader-auth__intro">
                        <div>
                            <div class="trader-auth__badge">{{ $isArabic ? 'بوابة تجار الجملة' : 'Wholesale Trader Portal' }}</div>
                            <h1>{{ $isArabic ? 'مساحة خاصة لتجار 400' : 'A dedicated space for 400 traders' }}</h1>
                            <p>{{ $isArabic ? 'سجل دخولك للوصول إلى منتجات الجملة المتاحة لحسابك وإنشاء طلبات بالسيريات ومتابعتها.' : 'Sign in to access wholesale products assigned to your account and track series-based orders.' }}</p>
                        </div>
                        <ul class="trader-auth__points">
                            <li>{{ $isArabic ? 'دخول برقم الحساب أو الجوال' : 'Login with account number or mobile' }}</li>
                            <li>{{ $isArabic ? 'حسابات التجار تدار من لوحة التحكم' : 'Trader accounts are managed by administration' }}</li>
                            <li>{{ $isArabic ? 'بوابة مستقلة عن حسابات العملاء' : 'Separate from customer accounts' }}</li>
                        </ul>
                    </div>

                    <div class="trader-auth__card">
                        <h2>{{ $isArabic ? 'تسجيل الدخول' : 'Sign in' }}</h2>
                        <div class="trader-auth__help">{{ $isArabic ? 'استخدم رقم الحساب أو رقم الجوال مع كلمة المرور المعتمدة من الإدارة.' : 'Use your account number or mobile with the password assigned by the administration.' }}</div>

                        @if ($errors->any())
                            <div class="trader-auth__error">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('front.trader.login.store') }}">
                            @csrf
                            <div class="trader-auth__field">
                                <label for="trader_login">{{ $isArabic ? 'رقم الحساب أو الجوال' : 'Account number or mobile' }}</label>
                                <input id="trader_login" type="text" name="login" value="{{ old('login') }}" dir="ltr" autocomplete="username" required>
                            </div>

                            <div class="trader-auth__field">
                                <label for="trader_password">{{ $isArabic ? 'كلمة المرور' : 'Password' }}</label>
                                <input id="trader_password" type="password" name="password" autocomplete="current-password" required>
                            </div>

                            <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                                {{ $isArabic ? 'تسجيل الدخول' : 'Login' }}
                            </button>

                            <div class="trader-auth__note">
                                {{ $isArabic ? 'لا يمكن إنشاء حساب تاجر من الموقع. يرجى التواصل مع الإدارة لاعتماد الحساب.' : 'Trader accounts cannot be created from the website. Please contact administration to activate access.' }}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('frontend.pages.trader.partials.footer')
@endsection
