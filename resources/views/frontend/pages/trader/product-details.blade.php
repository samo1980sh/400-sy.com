@extends('frontend.layouts.app')

@php
$locale = app()->getLocale();
$isArabic = $locale === 'ar';
$presentation = $product_presentation ?? [];

$title = (string) ($presentation['title'] ?? ($isArabic
? ($product->title_ar ?: $product->title_en ?: $product->model_no)
: ($product->title_en ?: $product->title_ar ?: $product->model_no)));

$groupName = $wholesale_group
? ($isArabic
? ($wholesale_group->name_ar ?? $wholesale_group->name_en ?? $wholesale_group->name ?? '#'.$wholesale_group->id)
: ($wholesale_group->name_en ?? $wholesale_group->name_ar ?? $wholesale_group->name ?? '#'.$wholesale_group->id))
: '-';

$categoryTitle = (string) ($presentation['category_name'] ?? '');

if ($categoryTitle === '' && $product->category) {
$categoryTitle = $isArabic
? ($product->category->name_ar ?? $product->category->title_ar ?? $product->category->name ?? '')
: ($product->category->name_en ?? $product->category->title_en ?? $product->category->name ?? '');
}

$availableColors = $product->wholesaleAvailabilities
->filter(fn ($availability) => (int) $availability->max_quantity > 0)
->values();

$colorsById = $product->wholesaleColors->keyBy('id');
$quantityRows = $product->wholesaleQuantities ?? collect();

$currency = (string) ($presentation['base_currency'] ?? ($isArabic ? ($product->currency_ar ?: 'ل.س') :
($product->currency_en ?: 'SYP')));

$unitPrice = (float) (
$presentation['base_price']
?? $presentation['price_current']
?? $presentation['compare_price']
?? $product->price
?? $product->compare_price
?? 0
);

$priceText = (string) (
$presentation['base_price_label']
?? $presentation['price_label']
?? $presentation['compare_price_label']
?? ($unitPrice > 0 ? number_format($unitPrice, 0).' '.$currency : ($isArabic ? 'السعر غير مضبوط' : 'Price not
configured'))
);

$mainImageUrl = (string) ($presentation['image'] ?? '');
@endphp

@section('title', $page_title ?? $title)
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
<style>
    .trader-product-details-wrap {
        padding: 64px 0 76px;
        background: radial-gradient(circle at 14% 18%, rgba(185, 134, 25, .08), transparent 28%), linear-gradient(180deg, #f8f7f4 0%, #fff 100%);
    }

    .trader-product-details-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 24px;
    }

    .trader-product-details-back,
    .trader-product-cart-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 8px 18px;
        border-radius: 999px;
        border: 1px solid rgba(0, 0, 0, .14);
        color: #111;
        background: #fff;
        font-weight: 800;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .trader-product-cart-link {
        background: #111;
        color: #fff;
        border-color: #111;
    }

    .trader-product-details-back:hover,
    .trader-product-cart-link:hover {
        transform: translateY(-2px);
        border-color: rgba(185, 134, 25, .42);
        box-shadow: 0 12px 28px rgba(0, 0, 0, .08);
        color: inherit;
    }

    .trader-product-alert {
        margin-bottom: 18px;
        border-radius: 18px;
        padding: 14px 18px;
        line-height: 1.8;
        font-weight: 800;
    }

    .trader-product-alert--success {
        border: 1px solid rgba(25, 135, 84, .25);
        background: rgba(25, 135, 84, .08);
        color: #0f5132;
    }

    .trader-product-alert--error {
        border: 1px solid rgba(220, 53, 69, .25);
        background: rgba(220, 53, 69, .08);
        color: #842029;
    }

    .trader-product-details-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.02fr) minmax(0, .98fr);
        gap: 28px;
        align-items: start;
    }

    .trader-product-details-media,
    .trader-product-details-info,
    .trader-product-details-section {
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 26px;
        background: #fff;
        box-shadow: 0 18px 44px rgba(0, 0, 0, .06);
    }

    .trader-product-details-media {
        min-height: 520px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 28px;
        position: sticky;
        top: 24px;
        overflow: hidden;
    }

    .trader-product-details-image {
        width: 100%;
        max-height: 620px;
        object-fit: contain;
        display: block;
        border-radius: 22px;
        background: #fafafa;
    }

    .trader-product-details-placeholder {
        width: 100%;
        min-height: 460px;
        border-radius: 22px;
        background: radial-gradient(circle at 20% 18%, rgba(185, 134, 25, .20), transparent 28%), linear-gradient(135deg, #111 0%, #3d321d 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 28px;
    }

    .trader-product-details-placeholder span {
        display: inline-flex;
        min-height: 30px;
        padding: 4px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .14);
        color: #fff;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 16px;
    }

    .trader-product-details-placeholder strong {
        color: #fff;
        font-size: clamp(28px, 4vw, 48px);
        line-height: 1.35;
    }

    .trader-product-details-info {
        padding: 28px;
    }

    .trader-product-details-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 4px 13px;
        border-radius: 999px;
        background: #111;
        color: #fff;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 16px;
    }

    .trader-product-details-info h1 {
        margin: 0 0 14px;
        color: #111;
        font-family: inherit;
        font-size: clamp(28px, 4vw, 46px);
        line-height: 1.28;
        font-weight: 800;
    }

    .trader-product-price-box {
        margin: 0 0 18px;
        border-radius: 18px;
        padding: 15px 18px;
        background: #f3ead8;
        color: #111;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .trader-product-price-box span {
        color: #6f5a28;
        font-weight: 800;
    }

    .trader-product-price-box strong {
        font-size: 24px;
        font-weight: 900;
    }

    .trader-product-details-meta {
        display: grid;
        gap: 10px;
        margin: 22px 0;
    }

    .trader-product-details-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 13px 0;
        border-bottom: 1px solid #ededed;
    }

    .trader-product-details-meta-row span {
        color: #777;
        font-size: 14px;
    }

    .trader-product-details-meta-row strong {
        color: #111;
        font-size: 15px;
        text-align: end;
    }

    .trader-product-details-colors {
        display: grid;
        gap: 14px;
        margin-top: 24px;
    }

    .trader-product-color-block {
        border: 1px solid #ececec;
        border-radius: 20px;
        background: #fcfcfc;
        padding: 16px;
    }

    .trader-product-color-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .trader-product-color-name {
        color: #111;
        font-size: 16px;
        font-weight: 800;
    }

    .trader-product-limit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 4px 11px;
        border-radius: 999px;
        background: #f3ead8;
        color: #111;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .trader-product-series-title {
        margin: 14px 0 8px;
        color: #777;
        font-size: 13px;
        font-weight: 800;
    }

    .trader-product-matrix {
        width: 100%;
        border: 1px solid #d9d9d9;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        margin-bottom: 10px;
    }

    .trader-product-matrix-row {
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: minmax(42px, 1fr);
        align-items: center;
        min-height: 36px;
    }

    .trader-product-matrix-row+.trader-product-matrix-row {
        border-top: 1px solid #e5e5e5;
    }

    .trader-product-matrix-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 5px 7px;
        color: #111;
        font-size: 14px;
        font-weight: 900;
        line-height: 1.2;
        border-inline-end: 1px solid #ededed;
    }

    .trader-product-matrix-cell:last-child {
        border-inline-end: 0;
    }

    .trader-product-matrix-row--quantities .trader-product-matrix-cell {
        background: #fafafa;
    }

    .trader-product-series-order {
        margin: 10px 0 18px;
        padding: 14px;
        border-radius: 16px;
        border: 1px solid #ececec;
        background: #fff;
    }

    .trader-product-series-order-row {
        display: grid;
        grid-template-columns: minmax(120px, 1fr) 110px auto;
        gap: 10px;
        align-items: end;
    }

    .trader-product-series-order label {
        display: block;
        color: #666;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 7px;
    }

    .trader-product-series-order input {
        width: 100%;
        min-height: 42px;
        border-radius: 12px;
        border: 1px solid #ddd;
        padding: 6px 12px;
        color: #111;
        background: #fff;
        font-weight: 800;
    }

    .trader-product-series-order button {
        min-height: 42px;
        border: 0;
        border-radius: 999px;
        background: #111;
        color: #fff;
        font-weight: 900;
        padding: 8px 18px;
        white-space: nowrap;
    }

    .trader-product-series-order-help {
        margin-top: 8px;
        color: #777;
        font-size: 12px;
        line-height: 1.7;
    }

    .trader-product-no-series {
        border: 1px dashed #d8d8d8;
        border-radius: 14px;
        padding: 14px;
        color: #777;
        font-size: 14px;
        line-height: 1.7;
        background: #fff;
    }

    .trader-product-details-section {
        margin-top: 26px;
        padding: 26px;
    }

    .trader-product-details-section h2 {
        margin: 0 0 12px;
        color: #111;
        font-family: inherit;
        font-size: 24px;
        font-weight: 800;
    }

    .trader-product-details-section p {
        margin: 0;
        color: #666;
        line-height: 1.9;
    }

    @media (max-width: 991px) {
        .trader-product-details-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        .trader-product-details-back,
        .trader-product-cart-link {
            width: fit-content;
        }

        .trader-product-details-shell {
            grid-template-columns: 1fr;
        }

        .trader-product-details-media {
            position: relative;
            top: auto;
            min-height: auto;
        }

        .trader-product-details-placeholder {
            min-height: 320px;
        }
    }

    @media (max-width: 575px) {
        .trader-product-details-wrap {
            padding: 46px 0 58px;
        }

        .trader-product-details-info,
        .trader-product-details-section,
        .trader-product-details-media {
            border-radius: 22px;
            padding: 20px;
        }

        .trader-product-color-head,
        .trader-product-details-meta-row,
        .trader-product-series-order-row,
        .trader-product-price-box {
            align-items: stretch;
            grid-template-columns: 1fr;
            flex-direction: column;
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
])

<main>
    @include('frontend.partials.page-title', [
    'title' => $page_title ?? $title,
    'subtitle' => $page_subtitle ?? '',
    'breadcrumbs' => $breadcrumb_items ?? [],
    'background' => $page_title_background ?? null,
    ])

    <section class="trader-product-details-wrap">
        <div class="container">
            <div class="trader-product-details-toolbar">
                <a href="{{ route('front.trader.products.index') }}" class="trader-product-details-back">
                    {{ $isArabic ? 'العودة لمنتجات الجملة' : 'Back to Wholesale Products' }}
                </a>
                <a href="{{ route('front.trader.cart.index') }}" class="trader-product-cart-link">
                    {{ $isArabic ? 'عرض طلب الجملة المؤقت' : 'View Wholesale Cart' }}
                </a>
            </div>

            @if (session('success'))
            <div class="trader-product-alert trader-product-alert--success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
            <div class="trader-product-alert trader-product-alert--error">{{ $errors->first() }}</div>
            @endif

            <div class="trader-product-details-shell">
                <div class="trader-product-details-media">
                    @if ($mainImageUrl !== '')
                    <img src="{{ $mainImageUrl }}" alt="{{ $title }}" class="trader-product-details-image">
                    @else
                    <div class="trader-product-details-placeholder">
                        <span>{{ $isArabic ? 'منتج جملة' : 'Wholesale Product' }}</span>
                        <strong>{{ $title }}</strong>
                    </div>
                    @endif
                </div>

                <div class="trader-product-details-info">
                    <span class="trader-product-details-badge">{{ $isArabic ? 'جملة' : 'Wholesale' }}</span>
                    <h1>{{ $title }}</h1>

                    <div class="trader-product-price-box">
                        <span>{{ $isArabic ? 'سعر القطعة' : 'Unit Price' }}</span>
                        <strong dir="ltr">{{ $priceText }}</strong>
                    </div>

                    <div class="trader-product-details-meta">
                        <div class="trader-product-details-meta-row">
                            <span>{{ $isArabic ? 'رمز المنتج' : 'Product Code' }}</span>
                            <strong dir="ltr">{{ $product->model_no }}</strong>
                        </div>
                        @if ($categoryTitle !== '')
                        <div class="trader-product-details-meta-row">
                            <span>{{ $isArabic ? 'التصنيف' : 'Category' }}</span>
                            <strong>{{ $categoryTitle }}</strong>
                        </div>
                        @endif
                        <div class="trader-product-details-meta-row">
                            <span>{{ $isArabic ? 'مجموعة الجملة' : 'Wholesale Group' }}</span>
                            <strong>{{ $groupName }}</strong>
                        </div>
                        <div class="trader-product-details-meta-row">
                            <span>{{ $isArabic ? 'الألوان المتاحة لحسابك' : 'Colors Available for You' }}</span>
                            <strong dir="ltr">{{ $availableColors->count() }}</strong>
                        </div>
                    </div>

                    <div class="trader-product-details-colors">
                        @forelse ($availableColors as $availability)
                        @php
                        $color = $colorsById->get($availability->product_wholesale_color_id);
                        $colorName = $color
                        ? ($isArabic
                        ? ($color->color_name_ar ?? $color->color_name_en ?? $color->color_code ?? '')
                        : ($color->color_name_en ?? $color->color_name_ar ?? $color->color_code ?? ''))
                        : '';

                        $maxSeries = (int) $availability->max_quantity;

                        $colorQuantities = $quantityRows
                        ->filter(fn ($row) => (int) $row->product_wholesale_color_id === (int)
                        $availability->product_wholesale_color_id && (int) $row->quantity > 0)
                        ->sortBy(function ($row) {
                        $size = trim((string) $row->size_text);
                        $sizeSort = is_numeric($size)
                        ? str_pad((string) ((int) $size), 8, '0', STR_PAD_LEFT)
                        : 'zzzzzzzz'.$size;

                        return str_pad((string) ((int) $row->series_group), 4, '0', STR_PAD_LEFT).'|'.$sizeSort;
                        })
                        ->values();

                        if ($colorQuantities->isEmpty()) {
                        $colorQuantities = $quantityRows
                        ->filter(fn ($row) => blank($row->product_wholesale_color_id) && (int) $row->quantity > 0)
                        ->sortBy(function ($row) {
                        $size = trim((string) $row->size_text);
                        $sizeSort = is_numeric($size)
                        ? str_pad((string) ((int) $size), 8, '0', STR_PAD_LEFT)
                        : 'zzzzzzzz'.$size;

                        return str_pad((string) ((int) $row->series_group), 4, '0', STR_PAD_LEFT).'|'.$sizeSort;
                        })
                        ->values();
                        }

                        $seriesGroups = $colorQuantities->groupBy(fn ($row) => (int) $row->series_group)->sortKeys();
                        @endphp

                        <div class="trader-product-color-block">
                            <div class="trader-product-color-head">
                                <span class="trader-product-color-name">
                                    {{ $colorName !== '' ? $colorName : ($isArabic ? 'لون جملة' : 'Wholesale Color') }}
                                </span>
                                <span class="trader-product-limit" dir="ltr">
                                    {{ $isArabic ? 'المتاح: ' : 'Available: ' }}{{ $maxSeries }}
                                    {{ $isArabic ? 'سيرية' : 'series' }}
                                </span>
                            </div>

                            @if ($seriesGroups->isEmpty())
                            <div class="trader-product-no-series">
                                {{ $isArabic ? 'لا توجد سيريات مضبوطة لهذا اللون بعد.' : 'No series quantities have been configured for this color yet.' }}
                            </div>
                            @else
                            @foreach ($seriesGroups as $seriesGroup => $seriesRows)
                            @php
                            $piecesPerSeries = $seriesRows->sum(fn ($row) => (int) $row->quantity);
                            $seriesTotal = $piecesPerSeries * $unitPrice;
                            @endphp

                            <div class="trader-product-series-title">
                                {{ $isArabic ? 'السيرية' : 'Series' }} <span dir="ltr">{{ $seriesGroup }}</span>
                                - {{ $isArabic ? 'عدد القطع' : 'Pieces' }} <span dir="ltr">{{ $piecesPerSeries }}</span>
                                - {{ $isArabic ? 'قيمة السيرية' : 'Series value' }} <span
                                    dir="ltr">{{ number_format($seriesTotal, 0) }} {{ $currency }}</span>
                            </div>

                            <div class="trader-product-matrix">
                                <div class="trader-product-matrix-row trader-product-matrix-row--sizes">
                                    @foreach ($seriesRows as $row)
                                    <span class="trader-product-matrix-cell" dir="ltr">{{ $row->size_text }}</span>
                                    @endforeach
                                </div>
                                <div class="trader-product-matrix-row trader-product-matrix-row--quantities">
                                    @foreach ($seriesRows as $row)
                                    <span class="trader-product-matrix-cell" dir="ltr">{{ (int) $row->quantity }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <form method="POST" action="{{ route('front.trader.orders.store', $product) }}"
                                class="trader-product-series-order">
                                @csrf
                                <input type="hidden" name="product_wholesale_color_id"
                                    value="{{ (int) $availability->product_wholesale_color_id }}">
                                <input type="hidden" name="series_group" value="{{ (int) $seriesGroup }}">

                                <div class="trader-product-series-order-row">
                                    <div>
                                        <label>{{ $isArabic ? 'عدد السيريات المطلوبة' : 'Requested series count' }}</label>
                                        <input type="number" name="series_count" value="1" min="1"
                                            max="{{ max(1, $maxSeries) }}" inputmode="numeric" dir="ltr"
                                            {{ $maxSeries < 1 ? 'disabled' : '' }}>
                                    </div>
                                    <div>
                                        <label>{{ $isArabic ? 'المتاح' : 'Available' }}</label>
                                        <input type="text" value="{{ $maxSeries }}" dir="ltr" disabled>
                                    </div>
                                    <button type="submit" {{ $maxSeries < 1 ? 'disabled' : '' }}>
                                        {{ $isArabic ? 'إضافة إلى طلب الجملة' : 'Add to Wholesale Cart' }}
                                    </button>
                                </div>

                                <div class="trader-product-series-order-help">
                                    {{ $isArabic
                                                        ? 'سيتم إضافة السيرية كاملة إلى طلب الجملة المؤقت. يمكنك إضافة منتجات وسيريات أخرى قبل إرسال الطلب النهائي.'
                                                        : 'The full series will be added to the wholesale cart. You can add more products and series before final submission.' }}
                                </div>
                            </form>
                            @endforeach
                            @endif
                        </div>
                        @empty
                        <div class="trader-product-no-series">
                            {{ $isArabic ? 'هذا المنتج غير متاح لمجموعة حسابك حالياً.' : 'This product is not currently available for your trader group.' }}
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="trader-product-details-section">
                <h2>{{ $isArabic ? 'وصف المنتج' : 'Product Description' }}</h2>
                <p>
                    {{ $isArabic
                            ? ($product->description_ar ?: 'لم يتم إدخال وصف لهذا المنتج بعد.')
                            : ($product->description_en ?: $product->description_ar ?: 'No description has been added for this product yet.') }}
                </p>
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
@include('frontend.partials.mobile-menu', ['navCategories' => $nav_categories ?? [], 'quickLinks' => $quick_links ??
[]])
@include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
@include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
@include('frontend.partials.auth-modals')
@endsection