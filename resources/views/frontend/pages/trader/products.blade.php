@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $groupName = $wholesale_group
        ? ($isArabic
            ? ($wholesale_group->name_ar ?? $wholesale_group->name_en ?? $wholesale_group->name ?? '#'.$wholesale_group->id)
            : ($wholesale_group->name_en ?? $wholesale_group->name_ar ?? $wholesale_group->name ?? '#'.$wholesale_group->id))
        : '-';
    $productsCount = method_exists($products, 'total') ? $products->total() : $products->count();
@endphp

@section('title', $page_title ?? ($isArabic ? 'منتجات الجملة' : 'Wholesale Products'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-products-wrap {
            padding: 64px 0 76px;
            background:
                radial-gradient(circle at 14% 18%, rgba(185, 134, 25, .08), transparent 28%),
                linear-gradient(180deg, #f8f7f4 0%, #ffffff 100%);
        }

        .trader-products-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 24px;
        }

        .trader-products-toolbar h2 {
            margin: 0;
            color: #111;
            font-family: inherit;
            font-size: clamp(26px, 3vw, 38px);
            line-height: 1.25;
            font-weight: 700;
        }

        .trader-products-toolbar p {
            margin: 8px 0 0;
            color: #6b6b6b;
            line-height: 1.8;
        }

        .trader-products-back {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 8px 18px;
            border-radius: 999px;
            border: 1px solid rgba(0,0,0,.14);
            color: #111;
            background: #fff;
            font-weight: 700;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .trader-products-back:hover {
            transform: translateY(-2px);
            border-color: rgba(185,134,25,.42);
            box-shadow: 0 12px 28px rgba(0,0,0,.08);
            color: #111;
        }

        .trader-products-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .trader-products-summary-card {
            min-height: 92px;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 22px;
            background: #fff;
            padding: 20px 22px;
            box-shadow: 0 16px 40px rgba(0,0,0,.045);
        }

        .trader-products-summary-card span {
            display: block;
            color: #777;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .trader-products-summary-card strong {
            display: block;
            color: #111;
            font-size: 22px;
            line-height: 1.35;
        }

        .trader-products-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .wholesale-product-card {
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 24px;
            background: #fff;
            padding: 20px;
            overflow: hidden;
            box-shadow: 0 18px 44px rgba(0,0,0,.06);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .wholesale-product-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 5px;
            background: linear-gradient(90deg, rgba(185,134,25,.85), rgba(17,17,17,.85));
        }

        .wholesale-product-card:hover {
            transform: translateY(-5px);
            border-color: rgba(185,134,25,.32);
            box-shadow: 0 24px 58px rgba(0,0,0,.09);
        }

        .wholesale-product-card__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .wholesale-product-card__badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: 4px 11px;
            border-radius: 999px;
            background: #111;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .wholesale-product-card__category {
            color: #777;
            font-size: 13px;
            line-height: 1.6;
            text-align: end;
        }

        .wholesale-product-card__title {
            margin: 0 0 8px;
            color: #111;
            font-family: inherit;
            font-size: 19px;
            line-height: 1.45;
            font-weight: 700;
        }

        .wholesale-product-card__model {
            color: #667085;
            font-size: 13px;
            letter-spacing: .03em;
        }

        .wholesale-product-card__quantity {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 0;
            border-top: 1px solid #ededed;
            border-bottom: 1px solid #ededed;
        }

        .wholesale-product-card__quantity span {
            color: #777;
            font-size: 13px;
        }

        .wholesale-product-card__quantity strong {
            color: #111;
            font-size: 20px;
        }

        .wholesale-product-card__colors {
            margin-top: auto;
        }

        .wholesale-product-card__colors-label {
            color: #777;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .wholesale-product-card__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .wholesale-product-card__chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 30px;
            padding: 5px 11px;
            border-radius: 999px;
            background: #fafafa;
            border: 1px solid #ececec;
            color: #111;
            font-size: 12px;
            font-weight: 700;
        }

        .wholesale-product-card__chip small {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            background: #f3ead8;
            color: #111;
            font-size: 11px;
        }

        .wholesale-product-card__action {
            margin-top: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 40px;
            border-radius: 999px;
            background: #f3ead8;
            color: #111;
            font-weight: 700;
            font-size: 13px;
        }



        .wholesale-product-card__color-block {
            border: 1px solid #ececec;
            border-radius: 18px;
            background: #fcfcfc;
            padding: 14px;
        }

        .wholesale-product-card__color-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .wholesale-product-card__color-name {
            color: #111;
            font-size: 14px;
            font-weight: 800;
        }

        .wholesale-product-card__availability-limit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 26px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f3ead8;
            color: #111;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .wholesale-product-card__series {
            display: grid;
            gap: 10px;
        }

        .wholesale-product-card__series-title {
            margin: 2px 0 0;
            color: #777;
            font-size: 12px;
            font-weight: 700;
        }

        .wholesale-product-card__matrix {
            width: 100%;
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .wholesale-product-card__matrix-row {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(34px, 1fr);
            align-items: center;
            min-height: 30px;
        }

        .wholesale-product-card__matrix-row + .wholesale-product-card__matrix-row {
            border-top: 1px solid #e5e5e5;
        }

        .wholesale-product-card__matrix-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 4px 6px;
            color: #111;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.2;
            border-inline-end: 1px solid #ededed;
        }

        .wholesale-product-card__matrix-cell:last-child {
            border-inline-end: 0;
        }

        .wholesale-product-card__matrix-row--sizes .wholesale-product-card__matrix-cell {
            background: #fff;
        }

        .wholesale-product-card__matrix-row--quantities .wholesale-product-card__matrix-cell {
            background: #fafafa;
        }

        .wholesale-product-card__no-series {
            border: 1px dashed #d8d8d8;
            border-radius: 12px;
            padding: 12px;
            color: #777;
            font-size: 13px;
            line-height: 1.7;
            background: #fff;
        }

        .trader-products-empty {
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 22px;
            background: #fff;
            padding: 28px;
            color: #333;
            box-shadow: 0 16px 40px rgba(0,0,0,.045);
        }

        @media (max-width: 1199px) {
            .trader-products-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 991px) {
            .trader-products-toolbar { align-items: stretch; flex-direction: column; }
            .trader-products-back { width: fit-content; }
            .trader-products-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 575px) {
            .trader-products-wrap { padding: 46px 0 58px; }
            .trader-products-summary,
            .trader-products-grid { grid-template-columns: 1fr; }
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
            'title' => $page_title ?? ($isArabic ? 'منتجات الجملة' : 'Wholesale Products'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-products-wrap">
            <div class="container">
                <div class="trader-products-toolbar">
                    <div>
                        <h2>{{ $isArabic ? 'المنتجات المتاحة لحسابك' : 'Products Available for Your Account' }}</h2>
                        <p>
                            {{ $isArabic
                                ? 'هذه الصفحة تعرض فقط منتجات الجملة المربوطة بمجموعة حسابك التجاري.'
                                : 'This page only shows wholesale products assigned to your trader group.' }}
                        </p>
                    </div>

                    <a href="{{ route('front.trader.dashboard') }}" class="trader-products-back">
                        {{ $isArabic ? 'العودة للوحة التاجر' : 'Back to Trader Dashboard' }}
                    </a>
                </div>

                <div class="trader-products-summary">
                    <div class="trader-products-summary-card">
                        <span>{{ $isArabic ? 'مجموعة الجملة' : 'Wholesale Group' }}</span>
                        <strong>{{ $groupName }}</strong>
                    </div>
                    <div class="trader-products-summary-card">
                        <span>{{ $isArabic ? 'عدد المنتجات المتاحة' : 'Available Products' }}</span>
                        <strong>{{ number_format($productsCount) }}</strong>
                    </div>
                </div>

                @if (! $wholesale_group)
                    <div class="trader-products-empty">
                        {{ $isArabic
                            ? 'لم يتم ربط حسابك بمجموعة جملة بعد. يرجى التواصل مع الإدارة.'
                            : 'Your account is not linked to a wholesale group yet. Please contact administration.' }}
                    </div>
                @elseif ($products->count() === 0)
                    <div class="trader-products-empty">
                        {{ $isArabic
                            ? 'لا توجد منتجات جملة متاحة لمجموعتك حالياً.'
                            : 'No wholesale products are currently available for your group.' }}
                    </div>
                @else
                    <div class="trader-products-grid">
                        @foreach ($products as $product)
                            @include('frontend.pages.trader.partials.wholesale-product-card', ['product' => $product])
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                @endif
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
