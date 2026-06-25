@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $currency = $isArabic ? 'ل.س' : 'SYP';
@endphp

@section('title', $page_title ?? ($isArabic ? 'طلب الجملة المؤقت' : 'Wholesale Cart'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-cart-wrap { padding: 64px 0 76px; background: radial-gradient(circle at 14% 18%, rgba(185,134,25,.08), transparent 28%), linear-gradient(180deg, #f8f7f4 0%, #fff 100%); }
        .trader-cart-toolbar { display:flex; justify-content:space-between; align-items:center; gap:14px; margin-bottom:22px; }
        .trader-cart-btn { min-height:42px; display:inline-flex; align-items:center; justify-content:center; padding:8px 18px; border-radius:999px; border:1px solid rgba(0,0,0,.12); background:#fff; color:#111; font-weight:900; }
        .trader-cart-btn--dark { background:#111; border-color:#111; color:#fff; }
        .trader-cart-alert { margin-bottom:18px; border-radius:18px; padding:14px 18px; line-height:1.8; font-weight:800; }
        .trader-cart-alert--success { border:1px solid rgba(25,135,84,.25); background:rgba(25,135,84,.08); color:#0f5132; }
        .trader-cart-alert--error { border:1px solid rgba(220,53,69,.25); background:rgba(220,53,69,.08); color:#842029; }
        .trader-cart-shell { display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:24px; align-items:start; }
        .trader-cart-list, .trader-cart-summary, .trader-cart-empty { border:1px solid rgba(0,0,0,.08); border-radius:26px; background:#fff; box-shadow:0 18px 44px rgba(0,0,0,.06); }
        .trader-cart-list { display:grid; gap:14px; padding:18px; }
        .trader-cart-item { display:grid; grid-template-columns:140px minmax(0,1fr); gap:18px; border:1px solid #eee; border-radius:20px; padding:14px; background:#fcfcfc; }
        .trader-cart-item-image { width:100%; height:150px; border-radius:16px; overflow:hidden; background:#f4f4f4; display:flex; align-items:center; justify-content:center; color:#777; text-align:center; font-weight:900; }
        .trader-cart-item-image img { width:100%; height:100%; object-fit:contain; display:block; background:#fff; }
        .trader-cart-item h3 { margin:0 0 6px; color:#111; font-size:20px; font-weight:900; line-height:1.45; }
        .trader-cart-meta { display:flex; flex-wrap:wrap; gap:8px; margin:8px 0 12px; }
        .trader-cart-pill { display:inline-flex; align-items:center; min-height:28px; padding:4px 10px; border-radius:999px; background:#f3ead8; color:#111; font-size:12px; font-weight:900; }
        .trader-cart-matrix { width:100%; border:1px solid #ddd; border-radius:12px; overflow:hidden; background:#fff; margin:10px 0; }
        .trader-cart-matrix-row { display:grid; grid-auto-flow:column; grid-auto-columns:minmax(42px,1fr); }
        .trader-cart-matrix-row + .trader-cart-matrix-row { border-top:1px solid #eee; }
        .trader-cart-matrix-cell { min-height:34px; display:flex; align-items:center; justify-content:center; padding:5px 7px; font-weight:900; border-inline-end:1px solid #eee; color:#111; }
        .trader-cart-matrix-cell:last-child { border-inline-end:0; }
        .trader-cart-update { display:grid; grid-template-columns:130px auto auto; gap:10px; align-items:end; margin-top:12px; }
        .trader-cart-update label { display:block; color:#666; font-size:12px; font-weight:900; margin-bottom:6px; }
        .trader-cart-update input { width:100%; min-height:40px; border-radius:12px; border:1px solid #ddd; padding:6px 10px; font-weight:900; color:#111; }
        .trader-cart-update button { min-height:40px; border:0; border-radius:999px; background:#111; color:#fff; padding:8px 16px; font-weight:900; white-space:nowrap; }
        .trader-cart-remove { min-height:40px; border:1px solid rgba(220,53,69,.28); border-radius:999px; background:rgba(220,53,69,.08); color:#842029; padding:8px 16px; font-weight:900; white-space:nowrap; }
        .trader-cart-summary { padding:22px; position:sticky; top:24px; }
        .trader-cart-summary h2 { margin:0 0 16px; font-size:24px; font-weight:900; color:#111; }
        .trader-cart-summary-row { display:flex; justify-content:space-between; gap:14px; padding:12px 0; border-bottom:1px solid #eee; }
        .trader-cart-summary-row span { color:#777; font-weight:800; }
        .trader-cart-summary-row strong { color:#111; font-weight:900; text-align:end; }
        .trader-cart-summary textarea { width:100%; min-height:96px; margin-top:16px; border-radius:16px; border:1px solid #ddd; padding:12px; color:#111; resize:vertical; }
        .trader-cart-submit { width:100%; min-height:48px; border:0; border-radius:999px; background:#111; color:#fff; font-weight:900; margin-top:14px; }
        .trader-cart-empty { padding:34px; text-align:center; color:#666; line-height:1.9; }
        @media (max-width:991px) { .trader-cart-shell { grid-template-columns:1fr; } .trader-cart-summary { position:relative; top:auto; } }
        @media (max-width:575px) { .trader-cart-toolbar { flex-direction:column; align-items:stretch; } .trader-cart-item { grid-template-columns:1fr; } .trader-cart-update { grid-template-columns:1fr; } }
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
            'title' => $page_title ?? ($isArabic ? 'طلب الجملة المؤقت' : 'Wholesale Cart'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-cart-wrap">
            <div class="container">
                <div class="trader-cart-toolbar">
                    <a href="{{ route('front.trader.products.index') }}" class="trader-cart-btn">
                        {{ $isArabic ? 'إضافة منتجات أخرى' : 'Add More Products' }}
                    </a>
                    <a href="{{ route('front.trader.dashboard') }}" class="trader-cart-btn trader-cart-btn--dark">
                        {{ $isArabic ? 'لوحة التاجر' : 'Trader Dashboard' }}
                    </a>
                </div>

                @if (session('success'))
                    <div class="trader-cart-alert trader-cart-alert--success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="trader-cart-alert trader-cart-alert--error">{{ $errors->first() }}</div>
                @endif

                @if (! empty($cart_warning))
                    <div class="trader-cart-alert trader-cart-alert--error">{{ $cart_warning }}</div>
                @endif

                @if (empty($cart_items))
                    <div class="trader-cart-empty">
                        <h2>{{ $isArabic ? 'طلب الجملة المؤقت فارغ' : 'Wholesale cart is empty' }}</h2>
                        <p>{{ $isArabic ? 'أضف سيريات من منتجات الجملة ثم ارجع إلى هذه الصفحة لإرسال الطلب.' : 'Add wholesale series from product details, then return here to submit the order.' }}</p>
                        <a href="{{ route('front.trader.products.index') }}" class="trader-cart-btn trader-cart-btn--dark">
                            {{ $isArabic ? 'تصفح منتجات الجملة' : 'Browse Wholesale Products' }}
                        </a>
                    </div>
                @else
                    <div class="trader-cart-shell">
                        <div class="trader-cart-list">
                            @foreach ($cart_items as $cartKey => $item)
                                @php
                                    $itemCurrency = $isArabic ? ($item['currency_ar'] ?? 'ل.س') : ($item['currency_en'] ?? 'SYP');
                                @endphp

                                <div class="trader-cart-item">
                                    <div class="trader-cart-item-image">
                                        @if (! empty($item['image_url']))
                                            <img src="{{ $item['image_url'] }}" alt="{{ $item['product_name'] }}">
                                        @else
                                            <span>{{ $item['product_name'] }}</span>
                                        @endif
                                    </div>

                                    <div>
                                        <h3>{{ $item['product_name'] }}</h3>

                                        <div class="trader-cart-meta">
                                            <span class="trader-cart-pill" dir="ltr">{{ $item['product_model_no'] }}</span>
                                            <span class="trader-cart-pill">{{ $item['color_name'] }}</span>
                                            <span class="trader-cart-pill">{{ $isArabic ? 'السيرية' : 'Series' }} <span dir="ltr">{{ $item['series_group'] }}</span></span>
                                            <span class="trader-cart-pill">{{ $isArabic ? 'سعر القطعة' : 'Unit Price' }} <span dir="ltr">{{ number_format((float) $item['unit_price'], 0) }} {{ $itemCurrency }}</span></span>
                                        </div>

                                        <div class="trader-cart-matrix">
                                            <div class="trader-cart-matrix-row">
                                                @foreach ($item['series_rows'] as $row)
                                                    <span class="trader-cart-matrix-cell" dir="ltr">{{ $row['size_text'] }}</span>
                                                @endforeach
                                            </div>
                                            <div class="trader-cart-matrix-row">
                                                @foreach ($item['series_rows'] as $row)
                                                    <span class="trader-cart-matrix-cell" dir="ltr">{{ (int) $row['quantity_per_series'] * (int) $item['series_count'] }}</span>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="trader-cart-meta">
                                            <span class="trader-cart-pill">{{ $isArabic ? 'عدد السيريات' : 'Series Count' }} <span dir="ltr">{{ $item['series_count'] }}</span></span>
                                            <span class="trader-cart-pill">{{ $isArabic ? 'عدد القطع' : 'Pieces' }} <span dir="ltr">{{ (int) $item['pieces_per_series'] * (int) $item['series_count'] }}</span></span>
                                            <span class="trader-cart-pill">{{ $isArabic ? 'المجموع' : 'Total' }} <span dir="ltr">{{ number_format((float) $item['line_total'], 0) }} {{ $itemCurrency }}</span></span>
                                        </div>

                                        <div class="trader-cart-update">
                                            <form method="POST" action="{{ route('front.trader.cart.update', $cartKey) }}">
                                                @csrf
                                                <label>{{ $isArabic ? 'عدد السيريات' : 'Series Count' }}</label>
                                                <input type="number" name="series_count" value="{{ (int) $item['series_count'] }}" min="1" max="{{ (int) $item['available_series'] }}" dir="ltr">
                                                <button type="submit">{{ $isArabic ? 'تحديث' : 'Update' }}</button>
                                            </form>

                                            <form method="POST" action="{{ route('front.trader.cart.remove', $cartKey) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="trader-cart-remove">{{ $isArabic ? 'حذف' : 'Remove' }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <aside class="trader-cart-summary">
                            <h2>{{ $isArabic ? 'ملخص الطلب' : 'Order Summary' }}</h2>
                            <div class="trader-cart-summary-row">
                                <span>{{ $isArabic ? 'عدد البنود' : 'Items' }}</span>
                                <strong dir="ltr">{{ (int) $cart_summary['items_count'] }}</strong>
                            </div>
                            <div class="trader-cart-summary-row">
                                <span>{{ $isArabic ? 'عدد السيريات' : 'Series Count' }}</span>
                                <strong dir="ltr">{{ (int) $cart_summary['series_count'] }}</strong>
                            </div>
                            <div class="trader-cart-summary-row">
                                <span>{{ $isArabic ? 'عدد القطع' : 'Pieces Count' }}</span>
                                <strong dir="ltr">{{ (int) $cart_summary['pieces_count'] }}</strong>
                            </div>
                            <div class="trader-cart-summary-row">
                                <span>{{ $isArabic ? 'الإجمالي' : 'Total' }}</span>
                                <strong dir="ltr">{{ number_format((float) $cart_summary['total'], 0) }} {{ $currency }}</strong>
                            </div>

                            <form method="POST" action="{{ route('front.trader.cart.submit') }}">
                                @csrf
                                <textarea name="notes" placeholder="{{ $isArabic ? 'ملاحظات الطلب إن وجدت' : 'Order notes if any' }}"></textarea>
                                <button type="submit" class="trader-cart-submit">
                                    {{ $isArabic ? 'إرسال طلب الجملة' : 'Submit Wholesale Order' }}
                                </button>
                            </form>
                        </aside>
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
