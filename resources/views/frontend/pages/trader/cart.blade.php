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
        .trader-cart { padding: 56px 0 72px; background: #fff; font-family: "Albert Sans", sans-serif; }
        .trader-cart__toolbar { display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 22px; }
        .trader-cart__toolbar-group { display: flex; flex-wrap: wrap; gap: 10px; }
        .trader-btn { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; padding: 8px 18px; border-radius: 3px; border: 1px solid #d8d8d8; background: #fff; color: #111; font-weight: 800; }
        .trader-btn:hover { color: #111; border-color: #111; background: #f7f7f7; }
        .trader-btn--dark { background: #111; border-color: #111; color: #fff; }
        .trader-btn--dark:hover { background: #333; border-color: #333; color: #fff; }
        .trader-alert { margin-bottom: 18px; border-radius: 3px; padding: 13px 16px; line-height: 1.8; font-weight: 700; }
        .trader-alert--success { border: 1px solid rgba(25,135,84,.25); background: rgba(25,135,84,.08); color: #0f5132; }
        .trader-alert--error { border: 1px solid rgba(220,53,69,.25); background: rgba(220,53,69,.08); color: #842029; }
        .trader-cart__shell { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 24px; align-items: start; }
        .trader-cart__list, .trader-cart__summary, .trader-cart__empty { border: 1px solid #e7e7e7; border-radius: 8px; background: #fff; }
        .trader-cart__list { display: grid; gap: 0; overflow: hidden; }
        .trader-cart__item { display: grid; grid-template-columns: 136px minmax(0, 1fr); gap: 18px; padding: 18px; border-bottom: 1px solid #eee; }
        .trader-cart__item:last-child { border-bottom: 0; }
        .trader-cart__image { width: 100%; aspect-ratio: 1 / 1.12; overflow: hidden; background: #f6f6f6; display: flex; align-items: center; justify-content: center; color: #777; text-align: center; font-weight: 800; padding: 10px; }
        .trader-cart__image img { width: 100%; height: 100%; object-fit: contain; display: block; background: #fff; }
        .trader-cart__item h3 { margin: 0 0 8px; color: #111; font-size: 20px; font-weight: 700; line-height: 1.45; }
        .trader-cart__meta { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0 12px; }
        .trader-pill { display: inline-flex; align-items: center; min-height: 28px; padding: 4px 10px; border-radius: 999px; background: #f5f5f5; color: #111; font-size: 12px; font-weight: 800; }
        .trader-cart__matrix { width: 100%; border: 1px solid #ddd; overflow-x: auto; background: #fff; margin: 10px 0; direction: ltr; }
        .trader-cart__matrix-row { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(42px, 1fr); }
        .trader-cart__matrix-row + .trader-cart__matrix-row { border-top: 1px solid #eee; }
        .trader-cart__matrix-cell { min-height: 34px; display: flex; align-items: center; justify-content: center; padding: 5px 7px; font-weight: 800; border-right: 1px solid #eee; color: #111; }
        .trader-cart__matrix-cell:last-child { border-right: 0; }
        .trader-cart__update { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin-top: 12px; }
        .trader-cart__update form { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin: 0; }
        .trader-cart__update label { display: block; color: #666; font-size: 12px; font-weight: 800; margin-bottom: 6px; }
        .trader-cart__update input { width: 128px; min-height: 40px; border-radius: 3px; border: 1px solid #ddd; padding: 6px 10px; font-weight: 800; color: #111; }
        .trader-cart__summary { padding: 22px; position: sticky; top: 24px; }
        .trader-cart__summary h2 { margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #111; }
        .trader-cart__summary-row { display: flex; justify-content: space-between; gap: 14px; padding: 12px 0; border-bottom: 1px solid #eee; }
        .trader-cart__summary-row span { color: #777; font-weight: 700; }
        .trader-cart__summary-row strong { color: #111; font-weight: 800; text-align: end; }
        .trader-cart__summary textarea { width: 100%; min-height: 96px; margin-top: 16px; border-radius: 3px; border: 1px solid #ddd; padding: 12px; color: #111; resize: vertical; }
        .trader-cart__submit { width: 100%; min-height: 48px; border: 0; border-radius: 3px; background: #111; color: #fff; font-weight: 900; margin-top: 14px; }
        .trader-cart__empty { padding: 34px; text-align: center; color: #666; line-height: 1.9; }
        .trader-cart__empty h2 { margin: 0 0 8px; color: #111; }
        @media (max-width: 991px) {
            .trader-cart__shell { grid-template-columns: 1fr; }
            .trader-cart__summary { position: relative; top: auto; }
        }
        @media (max-width: 575px) {
            .trader-cart__toolbar { display: block; }
            .trader-cart__toolbar-group { margin-top: 12px; }
            .trader-cart__item { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    @include('frontend.pages.trader.partials.header', ['traderCartCount' => $trader_cart_count ?? 0])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? ($isArabic ? 'طلب الجملة المؤقت' : 'Wholesale Cart'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-cart">
            <div class="container">
                <div class="trader-cart__toolbar">
                    <a href="{{ route('front.trader.products.index') }}" class="trader-btn">{{ $isArabic ? 'إضافة منتجات أخرى' : 'Add More Products' }}</a>
                    <div class="trader-cart__toolbar-group">
                        <a href="{{ route('front.trader.orders.index') }}" class="trader-btn">{{ $isArabic ? 'طلباتي' : 'My Orders' }}</a>
                        <a href="{{ route('front.trader.dashboard') }}" class="trader-btn trader-btn--dark">{{ $isArabic ? 'لوحة التاجر' : 'Trader Dashboard' }}</a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="trader-alert trader-alert--success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="trader-alert trader-alert--error">{{ $errors->first() }}</div>
                @endif

                @if (! empty($cart_warning))
                    <div class="trader-alert trader-alert--error">{{ $cart_warning }}</div>
                @endif

                @if (empty($cart_items))
                    <div class="trader-cart__empty">
                        <h2>{{ $isArabic ? 'طلب الجملة المؤقت فارغ' : 'Wholesale cart is empty' }}</h2>
                        <p>{{ $isArabic ? 'أضف سيريات من منتجات الجملة ثم ارجع إلى هذه الصفحة لإرسال الطلب.' : 'Add wholesale series from product details, then return here to submit the order.' }}</p>
                        <a href="{{ route('front.trader.products.index') }}" class="trader-btn trader-btn--dark">{{ $isArabic ? 'تصفح منتجات الجملة' : 'Browse Wholesale Products' }}</a>
                    </div>
                @else
                    <div class="trader-cart__shell">
                        <div class="trader-cart__list">
                            @foreach ($cart_items as $cartKey => $item)
                                @php($itemCurrency = $isArabic ? ($item['currency_ar'] ?? 'ل.س') : ($item['currency_en'] ?? 'SYP'))

                                <div class="trader-cart__item">
                                    <div class="trader-cart__image">
                                        @if (! empty($item['image_url']))
                                            <img src="{{ $item['image_url'] }}" alt="{{ $item['product_name'] }}">
                                        @else
                                            <span>{{ $item['product_name'] }}</span>
                                        @endif
                                    </div>

                                    <div>
                                        <h3>{{ $item['product_name'] }}</h3>
                                        <div class="trader-cart__meta">
                                            <span class="trader-pill" dir="ltr">{{ \Illuminate\Support\Str::substr((string) $item['product_model_no'], 3) }}</span>
                                            <span class="trader-pill">{{ $item['color_name'] }}</span>
                                            <span class="trader-pill">{{ $isArabic ? 'السيرية' : 'Series' }} <span dir="ltr">{{ $item['series_group'] }}</span></span>
                                            <span class="trader-pill">{{ $isArabic ? 'سعر القطعة' : 'Unit Price' }} <span dir="ltr">{{ number_format((float) $item['unit_price'], 0) }} {{ $itemCurrency }}</span></span>
                                        </div>

                                        <div class="trader-cart__matrix">
                                            <div class="trader-cart__matrix-row">
                                                @foreach ($item['series_rows'] as $row)
                                                    <span class="trader-cart__matrix-cell" dir="ltr">{{ $row['size_text'] }}</span>
                                                @endforeach
                                            </div>
                                            <div class="trader-cart__matrix-row">
                                                @foreach ($item['series_rows'] as $row)
                                                    <span class="trader-cart__matrix-cell" dir="ltr">{{ (int) $row['quantity_per_series'] * (int) $item['series_count'] }}</span>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="trader-cart__meta">
                                            <span class="trader-pill">{{ $isArabic ? 'عدد السيريات' : 'Series Count' }} <span dir="ltr">{{ $item['series_count'] }}</span></span>
                                            <span class="trader-pill">{{ $isArabic ? 'عدد القطع' : 'Pieces' }} <span dir="ltr">{{ (int) $item['pieces_per_series'] * (int) $item['series_count'] }}</span></span>
                                            <span class="trader-pill">{{ $isArabic ? 'المجموع' : 'Total' }} <span dir="ltr">{{ number_format((float) $item['line_total'], 0) }} {{ $itemCurrency }}</span></span>
                                        </div>

                                        <div class="trader-cart__update">
                                            <form method="POST" action="{{ route('front.trader.cart.update', $cartKey) }}">
                                                @csrf
                                                <div>
                                                    <label>{{ $isArabic ? 'عدد السيريات' : 'Series Count' }}</label>
                                                    <input type="number" name="series_count" value="{{ (int) $item['series_count'] }}" min="1" max="{{ (int) $item['available_series'] }}" dir="ltr">
                                                </div>
                                                <button type="submit" class="trader-btn trader-btn--dark">{{ $isArabic ? 'تحديث' : 'Update' }}</button>
                                            </form>

                                            <form method="POST" action="{{ route('front.trader.cart.remove', $cartKey) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="trader-btn">{{ $isArabic ? 'حذف' : 'Remove' }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <aside class="trader-cart__summary">
                            <h2>{{ $isArabic ? 'ملخص الطلب' : 'Order Summary' }}</h2>
                            <div class="trader-cart__summary-row"><span>{{ $isArabic ? 'عدد البنود' : 'Items' }}</span><strong dir="ltr">{{ (int) $cart_summary['items_count'] }}</strong></div>
                            <div class="trader-cart__summary-row"><span>{{ $isArabic ? 'عدد السيريات' : 'Series Count' }}</span><strong dir="ltr">{{ (int) $cart_summary['series_count'] }}</strong></div>
                            <div class="trader-cart__summary-row"><span>{{ $isArabic ? 'عدد القطع' : 'Pieces Count' }}</span><strong dir="ltr">{{ (int) $cart_summary['pieces_count'] }}</strong></div>
                            <div class="trader-cart__summary-row"><span>{{ $isArabic ? 'الإجمالي' : 'Total' }}</span><strong dir="ltr">{{ number_format((float) $cart_summary['total'], 0) }} {{ $currency }}</strong></div>

                            <form method="POST" action="{{ route('front.trader.cart.submit') }}">
                                @csrf
                                <textarea name="notes" placeholder="{{ $isArabic ? 'ملاحظات الطلب إن وجدت' : 'Order notes if any' }}"></textarea>
                                <button type="submit" class="trader-cart__submit">{{ $isArabic ? 'إرسال طلب الجملة' : 'Submit Wholesale Order' }}</button>
                            </form>
                        </aside>
                    </div>
                @endif
            </div>
        </section>
    </main>
    @include('frontend.pages.trader.partials.footer')
@endsection
