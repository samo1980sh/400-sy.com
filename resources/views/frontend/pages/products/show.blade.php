@extends('frontend.layouts.app')

@php
    $locale = $locale ?? app()->getLocale();
    $isArabic = $locale === 'ar';
    $product = is_array($product ?? null) ? $product : [];
    $productModel = $product_model ?? null;

    $colors = collect($product['colors'] ?? [])
        ->filter(fn ($color) => is_array($color) && filled($color['name'] ?? null))
        ->values();

    $requestedColorId = trim((string) request()->query('color_id', request()->query('product_color_id', '')));
    $requestedColorCode = trim((string) request()->query('color_code', ''));
    $requestedColorName = trim((string) request()->query('color', ''));

    $defaultColorIndex = $colors->search(function (array $color) use ($requestedColorId, $requestedColorCode, $requestedColorName): bool {
        if ($requestedColorId !== '' && (string) ($color['id'] ?? '') === $requestedColorId) {
            return true;
        }

        if ($requestedColorCode !== '' && mb_strtolower((string) ($color['color_code'] ?? '')) === mb_strtolower($requestedColorCode)) {
            return true;
        }

        if ($requestedColorName !== '' && mb_strtolower((string) ($color['name'] ?? '')) === mb_strtolower($requestedColorName)) {
            return true;
        }

        return false;
    });

    $defaultColorIndex = $defaultColorIndex === false ? 0 : (int) $defaultColorIndex;
    $defaultColor = $colors->get($defaultColorIndex) ?? [];
    $whatsappPhone = '963995010400';
    $whatsappProductCode = trim((string) ($product['product_code'] ?? ''));
    $whatsappDefaultColorCode = trim((string) ($defaultColor['color_code'] ?? ''));
    $whatsappIntro = $isArabic ? 'مرحبًا، أود الاستفسار عن المنتج:' : 'Hello, I would like to inquire about this product:';
    $whatsappProductLabel = $isArabic ? 'رمز المنتج' : 'Product code';
    $whatsappColorLabel = $isArabic ? 'رمز اللون' : 'Color code';
    $whatsappMessageLines = [$whatsappIntro];

    if ($whatsappProductCode !== '') {
        $whatsappMessageLines[] = $whatsappProductLabel . ': ' . $whatsappProductCode;
    }

    if ($whatsappDefaultColorCode !== '') {
        $whatsappMessageLines[] = $whatsappColorLabel . ': ' . $whatsappDefaultColorCode;
    }

    $whatsappInquiryUrl = 'https://wa.me/' . $whatsappPhone . '?text=' . rawurlencode(implode("\n", $whatsappMessageLines));
    $productShareUrl = url()->current();
    $productShareTitle = trim((string) ($product['title'] ?? ($page_title ?? __('front.brand'))));
    $productShareText = trim($productShareTitle . ($whatsappProductCode !== '' ? ' - ' . $whatsappProductCode : ''));
    $productShareWhatsAppUrl = 'https://wa.me/?text=' . rawurlencode(trim($productShareText . ' ' . $productShareUrl));
    $productShareFacebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($productShareUrl);
    $productShareXUrl = 'https://twitter.com/intent/tweet?url=' . rawurlencode($productShareUrl) . '&text=' . rawurlencode($productShareText);
    $wishlistActive = ! empty($product['is_in_wishlist']);
    $wishlistAddUrl = $product['wishlist_add_url'] ?? '';
    $wishlistRemoveUrl = $product['wishlist_remove_url'] ?? '';
    $wishlistProductSlug = $product['slug'] ?? '';
    $wishlistLabel = $wishlistActive
        ? __('front.wishlist.remove')
        : __('front.products.add_to_wishlist');


    $gallery = collect($defaultColor['gallery'] ?? [])
        ->merge($defaultColor['image'] ?? [])
        ->merge($product['gallery'] ?? [])
        ->merge($product['image'] ?? [])
        ->filter(fn ($image) => filled($image))
        ->unique()
        ->values();

    if ($gallery->isEmpty()) {
        $gallery = collect([asset('images/products/4brouwn1.jpg')]);
    }

    $gallerySlides = $colors
        ->flatMap(function (array $color) {
            $colorId = (int) ($color['id'] ?? 0);
            $colorName = trim((string) ($color['name'] ?? ''));
            $images = collect($color['gallery'] ?? [])
                ->prepend($color['image'] ?? null)
                ->filter(fn ($image) => filled($image))
                ->unique()
                ->values();

            return $images->map(fn ($image): array => [
                'image' => $image,
                'color_id' => $colorId,
                'color' => $colorName,
            ]);
        })
        ->values();

    if ($gallerySlides->isEmpty()) {
        $gallerySlides = $gallery->map(fn ($image): array => [
            'image' => $image,
            'color_id' => (int) ($defaultColor['id'] ?? 0),
            'color' => trim((string) ($defaultColor['name'] ?? '')),
        ]);
    }

    $normalizeSizeOption = static function ($size): ?array {
        if (is_object($size)) {
            $size = (array) $size;
        }

        if (is_string($size) || is_numeric($size)) {
            $size = ['size' => (string) $size];
        }

        if (! is_array($size)) {
            return null;
        }

        $value = trim((string) ($size['size'] ?? ($size['name'] ?? ($size['label'] ?? ($size['value'] ?? '')))));

        if ($value === '') {
            return null;
        }

        $soldOut = ! empty($size['is_sold_out'])
            || (($size['available'] ?? true) === false)
            || (array_key_exists('quantity', $size) && (int) $size['quantity'] <= 0);

        return array_replace($size, [
            'value' => $value,
            'is_sold_out_normalized' => $soldOut,
        ]);
    };

    $sizeOptions = collect($defaultColor['size_options'] ?? [])
        ->map($normalizeSizeOption)
        ->filter()
        ->values();

    $defaultSize = trim((string) ($defaultColor['default_size'] ?? ''));

    if ($defaultSize === '' && $sizeOptions->isNotEmpty()) {
        $defaultSize = (string) (($sizeOptions->firstWhere('is_sold_out_normalized', false)['value'] ?? null)
            ?: ($sizeOptions->first()['value'] ?? ''));
    }
    $description = trim((string) ($product['description'] ?? ''));
    $descriptionHtml = $description !== '' ? nl2br(e($description)) : '';
    $sizeChart = is_array($product['size_chart'] ?? null) ? $product['size_chart'] : [];
    $hasSizeChart = ! empty($product['has_size_chart']) && ! empty($sizeChart['columns'] ?? []) && ! empty($sizeChart['rows'] ?? []);
    $translateDisplayValue = static function (?string $value, string $type) use ($isArabic): string {
        $value = trim((string) $value);

        if ($value === '' || ! $isArabic) {
            return $value;
        }

        $maps = [
            'body_fit' => [
                'slim' => 'ضيق',
                'regular' => 'عادي',
                'wide' => 'واسع',
                'extra slim' => 'ضيق جداً',
                'extraslim' => 'ضيق جداً',
                'extra-slim' => 'ضيق جداً',
            ],
            'drop' => [
                'long' => 'طويل',
                'short' => 'قصير',
                'regular' => 'عادي',
            ],
        ];

        $key = mb_strtolower(str_replace(['_', '-'], ' ', $value));
        $key = trim(preg_replace('/\s+/', ' ', $key) ?? $key);

        return $maps[$type][$key] ?? $value;
    };

    $productInfoItems = collect([
        [
            'label' => $isArabic ? 'قصة الجسم' : 'Body Fit',
            'value' => $translateDisplayValue($product['body_fit'] ?? null, 'body_fit'),
        ],
        [
            'label' => $isArabic ? 'الدروب' : 'Drop',
            'value' => $translateDisplayValue($product['drop_type'] ?? null, 'drop'),
        ],
    ])->filter(fn (array $item): bool => filled($item['value'] ?? null))->values();

    $specifications = collect();

    if ($productModel && method_exists($productModel, 'relationLoaded') && $productModel->relationLoaded('details')) {
        $specifications = collect($productModel->getRelation('details'))
            ->filter(fn ($detail): bool => (bool) ($detail->is_active ?? true))
            ->map(function ($detail) use ($locale): array {
                $label = $locale === 'ar'
                    ? trim((string) ($detail->label_ar ?? ($detail->label_en ?? '')))
                    : trim((string) ($detail->label_en ?? ($detail->label_ar ?? '')));
                $value = $locale === 'ar'
                    ? trim((string) ($detail->value_ar ?? ($detail->value_en ?? '')))
                    : trim((string) ($detail->value_en ?? ($detail->value_ar ?? '')));

                return [
                    'label' => $label,
                    'value' => $value,
                ];
            })
            ->filter(fn (array $item): bool => filled($item['label'] ?? null) && filled($item['value'] ?? null))
            ->values();
    }

    $relatedProducts = collect($related_products ?? [])->values();

    $categoryUrl = $productModel?->category?->slug
        ? route('front.category', $productModel->category->slug)
        : route('front.home');

    $cartAddUrl = $product['cart_add_url'] ?? ($productModel?->slug ? route('front.cart.add', $productModel->slug) : '');

    $relatedTitle = $isArabic ? 'أكمل إطلالتك' : 'Complete Your Look';
@endphp

@section('title', $product['title'] ?? ($page_title ?? __('front.brand')))
@section('meta_description', $description !== '' ? $description : ($product['title'] ?? __('front.brand')))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/photoswipe.css') }}">
    <style>
        .detail-thumbs-slider { display: flex; gap: 10px; }
        .tf-product-info-title .product-card-badge { position: static; display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; background: rgba(255, 255, 255, 0.92); color: var(--text); font-size: 11px; font-weight: 700; letter-spacing: 0.03em; }
        .tf-product-info-title .product-card-badge.badge-offer { background: rgba(208, 70, 55, 0.1); color: #d04637; }
        .tf-product-info-title .product-card-badge.badge-best-seller { background: rgba(34, 139, 82, 0.1); color: #228b52; }
        .tf-product-info-title .product-card-badge.badge-new { background: rgba(33, 111, 219, 0.1); color: #216fdb; }
        @media (max-width: 767.98px) {
            .detail-thumbs-slider { flex-direction: column !important; }
            .detail-thumbs-slider > div { width: 100%; }
            .detail-thumbs-slider .tf-product-media-thumbs { order: 1; }
        }
    </style>
@endpush

@push('styles')
    <style>
        .product-detail-breadcrumb-wrap {
            gap: 18px;
        }

        .product-detail-breadcrumb-wrap .tf-breadcrumb-prev-next {
            flex: 0 0 auto;
        }

        .product-detail-breadcrumb-wrap.is-rtl .tf-breadcrumb-prev-next {
            padding-left: 25px;
        }

        .product-detail-breadcrumb-wrap.is-ltr .tf-breadcrumb-prev-next {
            padding-right: 25px;
        }

        @media (max-width: 767.98px) {
            .product-detail-breadcrumb-wrap.is-rtl .tf-breadcrumb-prev-next {
                padding-left: 12px;
            }

            .product-detail-breadcrumb-wrap.is-ltr .tf-breadcrumb-prev-next {
                padding-right: 12px;
            }
        }

        .tf-breadcrumb-prev-next .product-category-return-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-direction: row;
            width: auto;
            min-width: 0;
            height: 38px;
            gap: 7px;
            padding: 0 13px;
            border: 1px solid #111;
            border-radius: 6px;
            background: #111;
            color: #fff;
            box-shadow: none;
            text-decoration: none;
            transition: background .2s ease, color .2s ease, border-color .2s ease, transform .2s ease;
        }

        .tf-breadcrumb-prev-next .product-category-return-link:hover {
            background: #fff;
            color: #111;
            border-color: #111;
            transform: translateY(-1px);
        }

        .tf-breadcrumb-prev-next .product-category-return-link i {
            font-size: 15px;
            line-height: 1;
        }

        .tf-breadcrumb-prev-next .product-category-return-link span {
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        @media (max-width: 575.98px) {
            .tf-breadcrumb-prev-next .product-category-return-link {
                height: 36px;
                padding: 0 10px;
                gap: 6px;
            }

            .tf-breadcrumb-prev-next .product-category-return-link span {
                font-size: 11px;
            }
        }


        .product-detail-inline-info {
            margin: 22px 0 20px;
        }

        .product-detail-inline-info .widget-tabs {
            padding: 18px;
            border-radius: 8px;
            background: #fff;
        }

        .product-detail-inline-info .widget-menu-tab {
            margin-bottom: 14px;
        }

        .product-detail-inline-info .widget-menu-tab .item-title {
            font-size: 14px;
        }

        .product-detail-inline-info .tab-description p {
            margin-bottom: 0;
        }

        .product-title-fit-drop {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .product-title-fit-drop-badge {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            padding: 5px 11px;
            border: 1px solid #dedede;
            border-radius: 999px;
            background: #f7f7f7;
            color: #222;
            font-size: 11px;
            line-height: 1;
            white-space: nowrap;
        }

        .product-title-fit-drop-label {
            margin-inline-end: 4px;
            color: #777;
            font-weight: 500;
        }

        .product-title-fit-drop-value {
            font-weight: 700;
        }

        .product-detail-inline-info .tf-page-privacy-policy .d-flex:first-child {
            padding-top: 0 !important;
        }

        .product-detail-inline-info .tf-page-privacy-policy .d-flex:last-child {
            padding-bottom: 0 !important;
            border-bottom: 0 !important;
        }

        @media (max-width: 575.98px) {
            .product-detail-inline-info {
                margin: 18px 0;
            }

            .product-detail-inline-info .widget-tabs {
                padding: 14px;
            }
        }
    </style>
@endpush

@push('styles')
    <style>
        .product-detail-share-row {
            flex: 1 1 100%;
            width: 100%;
            margin-top: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .product-detail-share-row__title {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #333;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
        }

        .product-detail-share-row__title .icon {
            font-size: 15px;
        }

        .product-detail-share-row__icons {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .product-detail-share-row__item {
            width: 36px;
            height: 36px;
            padding: 0;
            border: 1px solid #dedede;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            text-decoration: none;
            font-size: 15px;
            line-height: 1;
            cursor: pointer;
            transition: color .18s ease, background-color .18s ease, border-color .18s ease, transform .18s ease;
        }

        .product-detail-share-row__item:hover,
        .product-detail-share-row__item:focus-visible {
            color: #fff;
            transform: translateY(-1px);
            outline: none;
        }

        .product-detail-share-row__item.is-whatsapp {
            color: #25a95b;
        }

        .product-detail-share-row__item.is-whatsapp:hover,
        .product-detail-share-row__item.is-whatsapp:focus-visible {
            border-color: #25d366;
            background: #25d366;
        }

        .product-detail-share-row__item.is-facebook {
            color: #1877f2;
        }

        .product-detail-share-row__item.is-facebook:hover,
        .product-detail-share-row__item.is-facebook:focus-visible {
            border-color: #1877f2;
            background: #1877f2;
        }

        .product-detail-share-row__item.is-x {
            color: #111;
        }

        .product-detail-share-row__item.is-x:hover,
        .product-detail-share-row__item.is-x:focus-visible {
            border-color: #111;
            background: #111;
        }

        .product-detail-share-row__item.is-copy {
            color: #555;
        }

        .product-detail-share-row__item.is-copy:hover,
        .product-detail-share-row__item.is-copy:focus-visible {
            border-color: #555;
            background: #555;
        }

        .product-detail-share-row__item .share-platform-icon {
            display: block;
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
            overflow: visible;
            color: inherit;
            pointer-events: none;
        }

        .product-detail-share-row__item .share-platform-icon--stroke {
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .product-detail-share-row__item .share-platform-icon--fill {
            fill: currentColor;
            stroke: none;
        }

        .product-detail-share-row__item:hover .share-platform-icon,
        .product-detail-share-row__item:focus-visible .share-platform-icon {
            color: #fff;
        }

        .product-detail-share-row__hint {
            min-height: 16px;
            color: #198754;
            font-size: 12px;
            font-weight: 600;
            opacity: 0;
            transition: opacity .18s ease;
        }

        .product-detail-share-row__hint.is-visible {
            opacity: 1;
        }

        @media (max-width: 575.98px) {
            .product-detail-share-row {
                gap: 8px;
            }

            .product-detail-share-row__item {
                width: 34px;
                height: 34px;
            }

            .product-detail-share-row__hint {
                flex-basis: 100%;
            }
        }
    </style>
@endpush

@section('content')
    @include('frontend.partials.announcement-bar', ['tickerItems' => $ticker_items ?? [], 'socialLinks' => $social_links ?? []])
    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'siteName' => $site_name ?? __('front.brand'),
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
    ])

    <main>
        <div class="tf-breadcrumb">
            <div class="container">
                <div class="tf-breadcrumb-wrap d-flex justify-content-between flex-wrap align-items-center product-detail-breadcrumb-wrap {{ $isArabic ? 'is-rtl' : 'is-ltr' }}">
                    <div class="tf-breadcrumb-list">
                        @foreach (($breadcrumb_items ?? []) as $crumb)
                            @if (! $loop->first)
                                <i class="icon {{ app()->getLocale() === 'ar' ? 'icon-arrow-left' : 'icon-arrow-right' }}"></i>
                            @endif

                            @if ($loop->last)
                                <span class="text">{{ $crumb['label'] ?? '' }}</span>
                            @else
                                <a href="{{ $crumb['url'] ?? '#' }}" class="text">{{ $crumb['label'] ?? '' }}</a>
                            @endif
                        @endforeach
                    </div>
                    <div class="tf-breadcrumb-prev-next">
                        <a href="{{ $categoryUrl }}" class="tf-breadcrumb-back product-category-return-link center" title="{{ $isArabic ? 'عرض منتجات نفس التصنيف' : 'View products in this category' }}" aria-label="{{ $isArabic ? 'عرض منتجات نفس التصنيف' : 'View products in this category' }}">
                            <i class="icon icon-shop" aria-hidden="true"></i>
                            <span>{{ $isArabic ? 'منتجات التصنيف' : 'Category products' }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <section class="flat-spacing-4 pt_0">
            <div class="tf-main-product section-image-zoom" data-detail-product='@json($product)' data-detail-default-color-index="{{ $defaultColorIndex }}">
                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="tf-product-media-wrap sticky-top">
                                <div class="detail-thumbs-slider" data-detail-media-shell>
                                    <div dir="ltr" class="swiper tf-product-media-thumbs other-image-zoom" data-direction="vertical" data-detail-thumbs-swiper>
                                        <div class="swiper-wrapper stagger-wrap" data-detail-thumbs>
                                            @foreach ($gallerySlides as $slide)
                                                <div class="swiper-slide stagger-item" data-color-id="{{ $slide['color_id'] ?? 0 }}" data-color="{{ $slide['color'] }}">
                                                    <div class="item">
                                                        <img class="lazyload" data-src="{{ $slide['image'] }}" src="{{ $slide['image'] }}" alt="{{ $product['title'] ?? '' }}">
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div dir="ltr" class="swiper tf-product-media-main" id="gallery-swiper-started" data-detail-main-swiper data-detail-gallery-lightbox>
                                        <div class="swiper-wrapper" data-detail-gallery>
                                            @foreach ($gallerySlides as $slide)
                                                <div class="swiper-slide" data-color-id="{{ $slide['color_id'] ?? 0 }}" data-color="{{ $slide['color'] }}">
                                                    <a href="{{ $slide['image'] }}" target="_blank" class="item" data-pswp-width="770" data-pswp-height="1075">
                                                        <img class="tf-image-zoom lazyload" data-zoom="{{ $slide['image'] }}" data-src="{{ $slide['image'] }}" src="{{ $slide['image'] }}" alt="{{ $product['title'] ?? '' }}">
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="swiper-button-next button-style-arrow thumbs-next"></div>
                                        <div class="swiper-button-prev button-style-arrow thumbs-prev"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="tf-product-info-wrap position-relative">
                                <div class="tf-zoom-main"></div>
                                <div class="tf-product-info-list other-image-zoom">
                                    <div class="tf-product-info-title">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h5 class="mb-0">{{ $product['title'] ?? '' }}</h5>
                                            @if (! empty($product['badge']))
                                                <span class="product-card-badge {{ $product['badge_class'] ?? '' }}">{{ $product['badge'] }}</span>
                                            @endif
                                        </div>

                                        @if ($productInfoItems->isNotEmpty())
                                            <div class="product-title-fit-drop" aria-label="{{ $isArabic ? 'قصة الجسم والدروب' : 'Body fit and drop' }}">
                                                @foreach ($productInfoItems as $infoItem)
                                                    <span class="product-title-fit-drop-badge">
                                                        <span class="product-title-fit-drop-label">{{ $infoItem['label'] }}:</span>
                                                        <span class="product-title-fit-drop-value">{{ $infoItem['value'] }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>


                                    <div class="tf-product-info-price">
                                        <div class="price-on-sale js-currency-price" data-detail-current-price data-base-price="{{ $product['price_current'] ?? $product['base_price'] ?? 0 }}" data-base-currency="{{ $product['base_currency'] ?? 'SYP' }}">
                                            {{ $product['price_current_label'] ?? $product['price_label'] ?? '' }}
                                        </div>
                                        <div class="compare-at-price js-currency-price {{ empty($product['compare_price_label']) ? 'd-none' : '' }}" data-detail-compare-price data-base-price="{{ $product['compare_price'] ?? 0 }}" data-base-currency="{{ $product['base_currency'] ?? 'SYP' }}">
                                            {{ $product['compare_price_label'] ?? '' }}
                                        </div>
                                    </div>

                                    @if (! empty($product['product_code']))
                                        <div class="tf-product-info-liveview">
                                            <p>
                                                {{ __('front.products.product_code') }}:
                                                <span class="fw-6" dir="ltr">
                                                    {{ $product['product_code'] }}<span data-detail-color-code-wrap class="{{ empty($defaultColor['color_code']) ? 'd-none' : '' }}">-<span data-detail-color-code>{{ $defaultColor['color_code'] ?? '' }}</span></span>
                                                </span>
                                            </p>
                                        </div>
                                    @endif

                                    <div class="tf-product-info-variant-picker">
                                        @if ($colors->isNotEmpty())
                                            <div class="variant-picker-item">
                                                <div class="variant-picker-label">
                                                    {{ __('front.products.color') }}:
                                                    <span class="fw-6 variant-picker-label-value" data-detail-color-label>{{ $defaultColor['name'] ?? '' }}</span>
                                                </div>
                                                <div class="variant-picker-values" data-detail-colors>
                                                    @foreach ($colors as $index => $color)
                                                        @php($swatchStyle = trim((string) ($color['swatch_style'] ?? '')))
                                                        <input id="detail-color-{{ $index }}" type="radio" name="detail_color" value="{{ $color['name'] }}" data-color-index="{{ $index }}" @checked($index === $defaultColorIndex)>
                                                        <label class="hover-tooltip radius-60 color-btn {{ $index === $defaultColorIndex ? 'active' : '' }}" for="detail-color-{{ $index }}" data-value="{{ $color['name'] }}" data-color="{{ $color['name'] }}" data-color-code="{{ $color['color_code'] ?? '' }}">
                                                            <span class="btn-checkbox {{ $color['class_name'] ?? 'four-Black' }}" style="{{ $swatchStyle !== '' ? $swatchStyle : '' }}"></span>
                                                            <span class="tooltip">{{ $color['name'] }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="variant-picker-item">
                                            <div data-detail-size-controls class="{{ $sizeOptions->isEmpty() ? 'd-none' : '' }}">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="variant-picker-label">
                                                        {{ __('front.products.size') }}:
                                                        <span class="fw-6 variant-picker-label-value" data-detail-size-label>{{ $defaultSize ?? '' }}</span>
                                                    </div>
                                                    @if ($hasSizeChart)
                                                        <button type="button" class="size-chart-pill btn-choose-size" data-detail-find-size>
                                                            <span class="size-chart-pill__icon" aria-hidden="true">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M4 7h16" />
                                                                    <path d="M4 17h16" />
                                                                    <path d="M7 4v16" />
                                                                    <path d="M17 4v16" />
                                                                </svg>
                                                            </span>
                                                            <span class="size-chart-pill__text">
                                                                <span class="size-chart-pill__title">{{ __('front.products.size_chart') }}</span>
                                                                <span class="size-chart-pill__subtitle">{{ __('front.products.find_your_size') }}</span>
                                                            </span>
                                                        </button>
                                                    @endif
                                                </div>
                                                <div class="variant-picker-values" data-detail-sizes>
                                                    @foreach ($sizeOptions as $index => $size)
                                                        <input type="radio" name="detail_size" id="detail-size-{{ $index }}" value="{{ $size['value'] }}" data-size-index="{{ $index }}" data-size-id="{{ $size['size_id'] ?? '' }}" data-size-code="{{ $size['size_code'] ?? '' }}" data-variant-id="{{ $size['variant_id'] ?? '' }}" data-product-color-id="{{ $size['product_color_id'] ?? '' }}" @checked(($size['value'] ?? '') === $defaultSize && empty($size['is_sold_out_normalized'])) @disabled(! empty($size['is_sold_out_normalized']))>
                                                        @if (! empty($size['is_sold_out_normalized']))
                                                            <span class="style-text disabled" data-value="{{ $size['value'] }}" aria-disabled="true">
                                                                <span class="size-label">{{ $size['value'] }}</span>
                                                            </span>
                                                        @else
                                                            <label class="style-text" for="detail-size-{{ $index }}" data-value="{{ $size['value'] }}">
                                                                <span class="size-label">{{ $size['value'] }}</span>
                                                            </label>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div
                                                class="text-muted small {{ $sizeOptions->isNotEmpty() ? 'd-none' : '' }}"
                                                data-detail-sizes-empty
                                                role="status"
                                            >
                                                {{ $isArabic ? 'لا يتوفر جدول قياسات مرتبط بهذا المنتج حالياً.' : 'No size table is currently available for this product.' }}
                                            </div>
                                        </div>
                                    </div>


                                    @if ($descriptionHtml !== '' || $specifications->isNotEmpty())
                                        <div class="product-detail-inline-info">
                                            <div class="widget-tabs style-has-border">
                                                <ul class="widget-menu-tab">
                                                    @if ($descriptionHtml !== '')
                                                        <li class="item-title active"><span class="inner">{{ $isArabic ? "\u{0648}\u{0635}\u{0641} \u{0627}\u{0644}\u{0645}\u{0646}\u{062a}\u{062c}" : 'Description' }}</span></li>
                                                    @endif
                                                    @if ($specifications->isNotEmpty())
                                                        <li class="item-title {{ $descriptionHtml === '' ? 'active' : '' }}"><span class="inner">{{ $isArabic ? "\u{0627}\u{0644}\u{0645}\u{0648}\u{0627}\u{0635}\u{0641}\u{0627}\u{062a}" : 'Additional Information' }}</span></li>
                                                    @endif
                                                </ul>
                                                <div class="widget-content-tab">
                                                    @if ($descriptionHtml !== '')
                                                        <div class="widget-content-inner active">
                                                            <div class="tab-description"><p>{!! $descriptionHtml !!}</p></div>
                                                        </div>
                                                    @endif
                                                    @if ($specifications->isNotEmpty())
                                                        <div class="widget-content-inner {{ $descriptionHtml === '' ? 'active' : '' }}">
                                                            <div class="tf-page-privacy-policy">
                                                                @foreach ($specifications as $spec)
                                                                    <div class="d-flex justify-content-between flex-wrap gap-3 py-3 border-bottom">
                                                                        <div class="fw-6">{{ $spec['label'] }}</div>
                                                                        <div>{!! nl2br(e($spec['value'])) !!}</div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="tf-product-info-buy-button">
                                        <form data-detail-cart-form data-cart-url="{{ $cartAddUrl }}">
                                            @csrf
                                            <button type="submit" class="tf-btn btn-fill justify-content-center fw-6 fs-16 flex-grow-1 animate-hover-btn btn-add-to-cart" data-detail-cart-submit @disabled(empty($cartAddUrl))>
                                                <span>{{ __('front.products.add_to_cart') }} -&nbsp;</span>
                                                <span class="tf-qty-price total-price js-currency-price" data-detail-submit-price data-base-price="{{ $product['price_current'] ?? $product['base_price'] ?? 0 }}" data-base-currency="{{ $product['base_currency'] ?? 'SYP' }}">{{ $product['price_current_label'] ?? $product['price_label'] ?? '' }}</span>
                                            </button>
                                            <a href="{{ $whatsappInquiryUrl }}" target="_blank" rel="noopener" class="tf-btn justify-content-center fw-6 fs-16 flex-grow-1 animate-hover-btn product-whatsapp-inquiry-btn" data-detail-whatsapp-inquiry data-whatsapp-phone="{{ $whatsappPhone }}" data-whatsapp-product-code="{{ $whatsappProductCode }}" data-whatsapp-intro="{{ $whatsappIntro }}" data-whatsapp-product-label="{{ $whatsappProductLabel }}" data-whatsapp-color-label="{{ $whatsappColorLabel }}">
                                                <span class="product-whatsapp-inquiry-btn__icon" aria-hidden="true">
                                                    <svg viewBox="0 0 32 32" focusable="false">
                                                        <path d="M16.01 3.2c-7.02 0-12.73 5.68-12.73 12.67 0 2.23.59 4.42 1.7 6.34L3.2 28.8l6.76-1.76a12.79 12.79 0 0 0 6.05 1.54c7.02 0 12.73-5.68 12.73-12.67S23.03 3.2 16.01 3.2Zm0 23.22c-1.9 0-3.75-.51-5.38-1.48l-.39-.23-4.01 1.04 1.07-3.89-.25-.4a10.46 10.46 0 0 1-1.61-5.59c0-5.8 4.74-10.52 10.57-10.52s10.57 4.72 10.57 10.52-4.74 10.55-10.57 10.55Zm5.8-7.89c-.32-.16-1.88-.93-2.17-1.03-.29-.11-.5-.16-.71.16-.21.32-.82 1.03-1 1.24-.18.21-.37.24-.69.08-.32-.16-1.35-.5-2.57-1.58-.95-.85-1.59-1.89-1.78-2.21-.18-.32-.02-.49.14-.65.14-.14.32-.37.48-.56.16-.18.21-.32.32-.53.11-.21.05-.4-.03-.56-.08-.16-.71-1.7-.98-2.33-.26-.61-.52-.53-.71-.54h-.61c-.21 0-.56.08-.85.4-.29.32-1.11 1.08-1.11 2.63s1.14 3.06 1.3 3.27c.16.21 2.24 3.41 5.43 4.78.76.33 1.35.52 1.81.67.76.24 1.45.21 2 .13.61-.09 1.88-.77 2.15-1.51.26-.74.26-1.38.18-1.51-.08-.13-.29-.21-.61-.37Z"/>
                                                    </svg>
                                                </span>
                                                <span>{{ $isArabic ? 'استفسار عبر واتساب' : 'Ask via WhatsApp' }}</span>
                                            </a>
                                            <a href="javascript:void(0);" class="tf-product-btn-wishlist hover-tooltip box-icon bg_white wishlist btn-icon-action {{ $wishlistActive ? 'active' : '' }}" data-wishlist-button data-product-slug="{{ $wishlistProductSlug }}" data-wishlist-add-url="{{ $wishlistAddUrl }}" data-wishlist-remove-url="{{ $wishlistRemoveUrl }}" data-wishlist-add-label="{{ __('front.products.add_to_wishlist') }}" data-wishlist-remove-label="{{ __('front.wishlist.remove') }}" aria-pressed="{{ $wishlistActive ? 'true' : 'false' }}" aria-label="{{ $wishlistLabel }}" title="{{ $wishlistLabel }}">
                                                <span class="icon icon-heart"></span>
                                                <span class="tooltip" data-wishlist-label>{{ $wishlistLabel }}</span>
                                                <span class="icon icon-delete"></span>
                                            </a>
                                        </form>
                                        <div class="product-detail-share-row" aria-label="{{ $isArabic ? 'مشاركة المنتج' : 'Share product' }}">
                                            <span class="product-detail-share-row__title">
                                                <i class="icon icon-share" aria-hidden="true"></i>
                                                {{ $isArabic ? 'مشاركة' : 'Share' }}
                                            </span>

                                            <div class="product-detail-share-row__icons">
                                                <a class="product-detail-share-row__item is-whatsapp" href="{{ $productShareWhatsAppUrl }}" target="_blank"
                                                    rel="noopener" aria-label="{{ $isArabic ? 'مشاركة عبر واتساب' : 'Share on WhatsApp' }}"
                                                    title="{{ $isArabic ? 'واتساب' : 'WhatsApp' }}">
                                                    <svg class="share-platform-icon share-platform-icon--stroke" viewBox="0 0 24 24"
                                                        aria-hidden="true" focusable="false">
                                                        <path d="M20 11.5a8.5 8.5 0 0 1-12.45 7.52L4 20l.98-3.55A8.5 8.5 0 1 1 20 11.5Z" />
                                                        <path d="M8.15 7.3c.45 3.42 2.36 5.34 5.78 5.78l1.08-1.08a.8.8 0 0 1 .9-.16l1.72.82a.8.8 0 0 1 .43.88c-.3 1.35-1.5 2.31-2.9 2.31-4.45 0-8.06-3.61-8.06-8.06 0-1.4.96-2.6 2.31-2.9a.8.8 0 0 1 .88.43l.82 1.72a.8.8 0 0 1-.16.9L9.87 9.02" />
                                                    </svg>
                                                </a>

                                                <a class="product-detail-share-row__item is-facebook" href="{{ $productShareFacebookUrl }}" target="_blank"
                                                    rel="noopener" aria-label="{{ $isArabic ? 'مشاركة عبر فيسبوك' : 'Share on Facebook' }}"
                                                    title="{{ $isArabic ? 'فيسبوك' : 'Facebook' }}">
                                                    <svg class="share-platform-icon share-platform-icon--fill" viewBox="0 0 24 24"
                                                        aria-hidden="true" focusable="false">
                                                        <path d="M13.7 22v-8h2.7l.4-3.1h-3.1V8.93c0-.9.25-1.51 1.55-1.51h1.66V4.65a22.4 22.4 0 0 0-2.42-.13c-2.4 0-4.04 1.46-4.04 4.15v2.23H7.73V14h2.72v8h3.25Z" />
                                                    </svg>
                                                </a>

                                                <a class="product-detail-share-row__item is-x" href="{{ $productShareXUrl }}" target="_blank" rel="noopener"
                                                    aria-label="{{ $isArabic ? 'مشاركة عبر إكس' : 'Share on X' }}" title="X">
                                                    <svg class="share-platform-icon share-platform-icon--stroke" viewBox="0 0 24 24"
                                                        aria-hidden="true" focusable="false">
                                                        <path d="M5 4 19 20" />
                                                        <path d="M19 4 5 20" />
                                                    </svg>
                                                </a>

                                                <button type="button" class="product-detail-share-row__item is-copy"
                                                    data-product-detail-copy-share="{{ $productShareUrl }}"
                                                    aria-label="{{ $isArabic ? 'نسخ رابط المنتج' : 'Copy product link' }}"
                                                    title="{{ $isArabic ? 'نسخ الرابط' : 'Copy link' }}">
                                                    <svg class="share-platform-icon share-platform-icon--stroke" viewBox="0 0 24 24"
                                                        aria-hidden="true" focusable="false">
                                                        <path d="M10.6 13.4a4 4 0 0 0 5.66.06l2.2-2.2a4 4 0 1 0-5.66-5.66l-1.27 1.27" />
                                                        <path d="M13.4 10.6a4 4 0 0 0-5.66-.06l-2.2 2.2a4 4 0 1 0 5.66 5.66l1.27-1.27" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <span class="product-detail-share-row__hint" data-product-detail-copy-status aria-live="polite">
                                                {{ $isArabic ? 'تم نسخ الرابط' : 'Link copied' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        @if ($relatedProducts->isNotEmpty())
            <section class="flat-spacing-1 pt_0">
                <div class="container">
                    <div class="flat-title"><span class="title">{{ $relatedTitle }}</span></div>
                    <div class="grid-layout wrapper-shop" data-grid="grid-4">
                        @foreach ($relatedProducts as $relatedProduct)
                            @include('frontend.partials.product-card', ['product' => $relatedProduct, 'loadmore_hidden' => false])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>

    @include('frontend.partials.footer', ['contact' => $contact ?? null, 'socialLinks' => $social_links ?? [], 'footerPages' => $footer_pages ?? [], 'collections' => $collections ?? []])
    @include('frontend.partials.toolbar-bottom', ['cartCount' => $cart_count ?? 0, 'wishlistCount' => $wishlist_count ?? 0, 'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index')])
    @include('frontend.partials.mobile-menu', ['navCategories' => $nav_categories ?? [], 'quickLinks' => $quick_links ?? []])
    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
    @include('frontend.partials.quick-add')
    @include('frontend.partials.quick-view')
    @include('frontend.partials.find-size')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-product-detail-copy-share]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var url = button.getAttribute('data-product-detail-copy-share') || window.location.href;
                    var shareRow = button.closest('.product-detail-share-row');
                    var status = shareRow ? shareRow.querySelector('[data-product-detail-copy-status]') : null;

                    function showCopiedStatus() {
                        if (!status) {
                            return;
                        }

                        clearTimeout(button.productDetailCopyTimer);
                        status.classList.add('is-visible');
                        button.productDetailCopyTimer = setTimeout(function () {
                            status.classList.remove('is-visible');
                        }, 1800);
                    }

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(url).then(showCopiedStatus).catch(function () {
                            window.prompt(url, url);
                        });
                        return;
                    }

                    window.prompt(url, url);
                });
            });
        });
    </script>
@endsection

@push('scripts')
    @include('frontend.partials.product-scripts')
    @include('frontend.partials.product-detail-scripts')
@endpush
