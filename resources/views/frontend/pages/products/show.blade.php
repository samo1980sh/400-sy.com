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
            $colorName = trim((string) ($color['name'] ?? ''));
            $images = collect($color['gallery'] ?? [])
                ->prepend($color['image'] ?? null)
                ->filter(fn ($image) => filled($image))
                ->unique()
                ->values();

            return $images->map(fn ($image): array => [
                'image' => $image,
                'color' => $colorName,
            ]);
        })
        ->values();

    if ($gallerySlides->isEmpty()) {
        $gallerySlides = $gallery->map(fn ($image): array => [
            'image' => $image,
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
        ->whenEmpty(fn () => collect($product['size_options'] ?? []))
        ->map($normalizeSizeOption)
        ->filter()
        ->values();

    $defaultSize = trim((string) ($defaultColor['default_size'] ?? ($product['default_size'] ?? '')));

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

        .product-detail-description-badges {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .product-detail-description-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .product-detail-fit-badge {
            border: 1px solid #dedede;
            background: #f7f7f7;
            color: #222;
            font-weight: 600;
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
@section('content')
    @include('frontend.partials.announcement-bar', ['tickerItems' => $ticker_items ?? [], 'socialLinks' => $social_links ?? []])
    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'siteName' => $site_name ?? __('front.brand'),
        'cartCount' => $cart_count ?? 0,
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
                                                <div class="swiper-slide stagger-item" data-color="{{ $slide['color'] }}">
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
                                                <div class="swiper-slide" data-color="{{ $slide['color'] }}">
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
                                            <p>{{ __('front.products.product_code') }}: <span class="fw-6" dir="ltr">{{ $product['product_code'] }}@if (! empty($defaultColor['color_code']))-<span data-detail-color-code>{{ $defaultColor['color_code'] }}</span>@endif</span></p>
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

                                        @if ($sizeOptions->isNotEmpty())
                                            <div class="variant-picker-item">
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
                                        @endif
                                    </div>


                                    @if ($descriptionHtml !== '' || $productInfoItems->isNotEmpty() || $specifications->isNotEmpty())
                                        <div class="product-detail-inline-info">
                                            <div class="widget-tabs style-has-border">
                                                <ul class="widget-menu-tab">
                                                    @if ($descriptionHtml !== '' || $productInfoItems->isNotEmpty())
                                                        <li class="item-title active"><span class="inner">{{ $isArabic ? "\u{0648}\u{0635}\u{0641} \u{0627}\u{0644}\u{0645}\u{0646}\u{062a}\u{062c}" : 'Description' }}</span></li>
                                                    @endif
                                                    @if ($specifications->isNotEmpty())
                                                        <li class="item-title {{ ($descriptionHtml === '' && $productInfoItems->isEmpty()) ? 'active' : '' }}"><span class="inner">{{ $isArabic ? "\u{0627}\u{0644}\u{0645}\u{0648}\u{0627}\u{0635}\u{0641}\u{0627}\u{062a}" : 'Additional Information' }}</span></li>
                                                    @endif
                                                </ul>
                                                <div class="widget-content-tab">
                                                    @if ($descriptionHtml !== '' || $productInfoItems->isNotEmpty())
                                                        <div class="widget-content-inner active">
                                                            @if ($productInfoItems->isNotEmpty())
                                                                <div class="product-detail-description-badges" aria-label="{{ $isArabic ? 'شارات المنتج والقصة' : 'Product badges and fit' }}">
                                                                    @foreach ($productInfoItems as $infoItem)
                                                                        <span class="product-detail-description-badge product-detail-fit-badge">{{ $infoItem['value'] }}</span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            @if ($descriptionHtml !== '')
                                                                <div class="tab-description"><p>{!! $descriptionHtml !!}</p></div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if ($specifications->isNotEmpty())
                                                        <div class="widget-content-inner {{ ($descriptionHtml === '' && $productInfoItems->isEmpty()) ? 'active' : '' }}">
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
                                            <a href="javascript:void(0);" class="tf-product-btn-wishlist hover-tooltip box-icon bg_white wishlist btn-icon-action">
                                                <span class="icon icon-heart"></span>
                                                <span class="tooltip">{{ __('front.products.add_to_wishlist') }}</span>
                                                <span class="icon icon-delete"></span>
                                            </a>
                                        </form>
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
    @include('frontend.partials.toolbar-bottom', ['cartCount' => $cart_count ?? 0])
    @include('frontend.partials.mobile-menu', ['navCategories' => $nav_categories ?? [], 'quickLinks' => $quick_links ?? []])
    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
    @include('frontend.partials.quick-add')
    @include('frontend.partials.quick-view')
    @include('frontend.partials.find-size')
@endsection

@push('scripts')
    @include('frontend.partials.product-scripts')
    @include('frontend.partials.product-detail-scripts')
@endpush
