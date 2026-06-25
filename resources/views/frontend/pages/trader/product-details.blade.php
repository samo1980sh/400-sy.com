@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $title = $isArabic
        ? ($product->title_ar ?: $product->title_en ?: $product->model_no)
        : ($product->title_en ?: $product->title_ar ?: $product->model_no);

    $groupName = $wholesale_group
        ? ($isArabic
            ? ($wholesale_group->name_ar ?? $wholesale_group->name_en ?? $wholesale_group->name ?? '#'.$wholesale_group->id)
            : ($wholesale_group->name_en ?? $wholesale_group->name_ar ?? $wholesale_group->name ?? '#'.$wholesale_group->id))
        : '-';

    $categoryTitle = '';
    if ($product->category) {
        $categoryTitle = $isArabic
            ? ($product->category->name_ar ?? $product->category->title_ar ?? $product->category->name ?? '')
            : ($product->category->name_en ?? $product->category->title_en ?? $product->category->name ?? '');
    }

    $availableColors = $product->wholesaleAvailabilities
        ->filter(fn ($availability) => (int) $availability->max_quantity > 0)
        ->values();
    $colorsById = $product->wholesaleColors->keyBy('id');
    $quantityRows = $product->wholesaleQuantities ?? collect();
@endphp

@section('title', $page_title ?? $title)
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-product-details-wrap {
            padding: 64px 0 76px;
            background:
                radial-gradient(circle at 14% 18%, rgba(185, 134, 25, .08), transparent 28%),
                linear-gradient(180deg, #f8f7f4 0%, #ffffff 100%);
        }

        .trader-product-details-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 24px;
        }

        .trader-product-details-back {
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

        .trader-product-details-back:hover {
            transform: translateY(-2px);
            border-color: rgba(185,134,25,.42);
            box-shadow: 0 12px 28px rgba(0,0,0,.08);
            color: #111;
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
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 18px 44px rgba(0,0,0,.06);
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

        .trader-product-details-placeholder {
            width: 100%;
            min-height: 460px;
            border-radius: 22px;
            background:
                radial-gradient(circle at 20% 18%, rgba(185, 134, 25, .20), transparent 28%),
                linear-gradient(135deg, #111 0%, #3d321d 100%);
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
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
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
            margin: 6px 0 8px;
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

        .trader-product-matrix-row + .trader-product-matrix-row {
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

        .trader-product-no-series {
            border: 1px dashed #d8d8d8;
            border-radius: 14px;
            padding: 14px;
            color: #777;
            font-size: 14px;
            line-height: 1.7;
            background: #fff;
        }

        .trader-product-next-action {
            margin-top: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            border-radius: 999px;
            background: #111;
            color: #fff;
            font-weight: 800;
            border: 0;
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
            .trader-product-details-toolbar { align-items: stretch; flex-direction: column; }
            .trader-product-details-back { width: fit-content; }
            .trader-product-details-shell { grid-template-columns: 1fr; }
            .trader-product-details-media { position: relative; top: auto; min-height: auto; }
            .trader-product-details-placeholder { min-height: 320px; }
        }

        @media (max-width: 575px) {
            .trader-product-details-wrap { padding: 46px 0 58px; }
            .trader-product-details-info,
            .trader-product-details-section,
            .trader-product-details-media { border-radius: 22px; padding: 20px; }
            .trader-product-color-head,
            .trader-product-details-meta-row { align-items: flex-start; flex-direction: column; }
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
                </div>

                <div class="trader-product-details-shell">
                    <div class="trader-product-details-media">
                        <div class="trader-product-details-placeholder">
                            <span>{{ $isArabic ? 'منتج جملة' : 'Wholesale Product' }}</span>
                            <strong>{{ $title }}</strong>
                        </div>
                    </div>

                    <div class="trader-product-details-info">
                        <span class="trader-product-details-badge">{{ $isArabic ? 'جملة' : 'Wholesale' }}</span>
                        <h1>{{ $title }}</h1>

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

                                    $colorQuantities = $quantityRows
                                        ->filter(fn ($row) => (int) $row->product_wholesale_color_id === (int) $availability->product_wholesale_color_id && (int) $row->quantity > 0)
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
                                            {{ $isArabic ? 'حد المجموعة: ' : 'Group limit: ' }}{{ (int) $availability->max_quantity }}
                                        </span>
                                    </div>

                                    @if ($seriesGroups->isEmpty())
                                        <div class="trader-product-no-series">
                                            {{ $isArabic
                                                ? 'لا توجد سيريات مضبوطة لهذا اللون بعد.'
                                                : 'No series quantities have been configured for this color yet.' }}
                                        </div>
                                    @else
                                        @foreach ($seriesGroups as $seriesGroup => $seriesRows)
                                            @if ($seriesGroups->count() > 1)
                                                <div class="trader-product-series-title">
                                                    {{ $isArabic ? 'السيريا' : 'Series' }} <span dir="ltr">{{ $seriesGroup }}</span>
                                                </div>
                                            @endif

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
                                        @endforeach
                                    @endif
                                </div>
                            @empty
                                <div class="trader-product-no-series">
                                    {{ $isArabic
                                        ? 'هذا المنتج غير متاح لمجموعة حسابك حالياً.'
                                        : 'This product is not currently available for your trader group.' }}
                                </div>
                            @endforelse
                        </div>

                        <button type="button" class="trader-product-next-action" disabled>
                            {{ $isArabic ? 'اختيار المقاس وإنشاء الطلب في المرحلة التالية' : 'Size selection and ordering in the next phase' }}
                        </button>
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
    @include('frontend.partials.mobile-menu', ['navCategories' => $nav_categories ?? [], 'quickLinks' => $quick_links ?? []])
    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
@endsection
