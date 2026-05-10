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

    $specifications = collect($product['specifications'] ?? [])
        ->map(function ($specification): ?array {
            if (is_object($specification)) {
                $specification = (array) $specification;
            }

            if (! is_array($specification)) {
                return null;
            }

            return [
                'label' => trim((string) ($specification['label'] ?? '')),
                'value' => trim((string) ($specification['value'] ?? '')),
            ];
        })
        ->filter(fn (?array $item): bool => is_array($item) && filled($item['label'] ?? null) && filled($item['value'] ?? null))
        ->values();

    if ($specifications->isEmpty() && $productModel && method_exists($productModel, 'relationLoaded') && $productModel->relationLoaded('details')) {
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
@endpush

@section('content')
    @include('frontend.partials.announcement-bar', ['tickerItems' => $ticker_items ?? [], 'socialLinks' => $social_links ?? []])
    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'siteName' => $site_name ?? config('app.name', '400 Four HUNDRED'),
        'cartCount' => $cart_count ?? 0,
    ])

    <main>
        <div class="tf-breadcrumb">
            <div class="container">
                <div class="tf-breadcrumb-wrap d-flex justify-content-between flex-wrap align-items-center">
                    <div class="tf-breadcrumb-list">
                        @foreach (($breadcrumb_items ?? []) as $crumb)
                            @if (! $loop->first)
                                <i class="icon icon-arrow-right"></i>
                            @endif

                            @if ($loop->last)
                                <span class="text">{{ $crumb['label'] ?? '' }}</span>
                            @else
                                <a href="{{ $crumb['url'] ?? '#' }}" class="text">{{ $crumb['label'] ?? '' }}</a>
                            @endif
                        @endforeach
                    </div>
                    <div class="tf-breadcrumb-prev-next">
                        <a href="{{ $categoryUrl }}" class="tf-breadcrumb-back hover-tooltip center">
                            <i class="icon icon-shop"></i>
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
                                <div class="thumbs-slider" data-detail-media-shell>
                                    <div dir="ltr" class="swiper tf-product-media-thumbs other-image-zoom" data-direction="vertical" data-detail-thumbs-swiper>
                                        <div class="swiper-wrapper stagger-wrap" data-detail-thumbs>
                                            @foreach ($gallery as $image)
                                                <div class="swiper-slide stagger-item" data-color="{{ $defaultColor['name'] ?? '' }}">
                                                    <div class="item">
                                                        <img class="lazyload" data-src="{{ $image }}" src="{{ $image }}" alt="{{ $product['title'] ?? '' }}">
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div dir="ltr" class="swiper tf-product-media-main" id="gallery-swiper-started" data-detail-main-swiper data-detail-gallery-lightbox>
                                        <div class="swiper-wrapper" data-detail-gallery>
                                            @foreach ($gallery as $image)
                                                <div class="swiper-slide" data-color="{{ $defaultColor['name'] ?? '' }}">
                                                    <a href="{{ $image }}" target="_blank" class="item" data-pswp-width="770" data-pswp-height="1075">
                                                        <img class="tf-image-zoom lazyload" data-zoom="{{ $image }}" data-src="{{ $image }}" src="{{ $image }}" alt="{{ $product['title'] ?? '' }}">
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="swiper-button-next button-style-arrow single-slide-prev"></div>
                                        <div class="swiper-button-prev button-style-arrow single-slide-next"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="tf-product-info-wrap position-relative">
                                <div class="tf-zoom-main"></div>
                                <div class="tf-product-info-list other-image-zoom">
                                    <div class="tf-product-info-title">
                                        <h5>{{ $product['title'] ?? '' }}</h5>
                                    </div>

                                    @if (! empty($product['badge']))
                                        <div class="tf-product-info-badges">
                                            <div class="badges {{ $product['badge_class'] ?? '' }}">{{ $product['badge'] }}</div>
                                        </div>
                                    @endif

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
                                            <p>{{ __('front.products.product_code') }}: <span class="fw-6">{{ $product['product_code'] }}</span></p>
                                        </div>
                                    @endif

                                    @if ($productInfoItems->isNotEmpty())
                                        @foreach ($productInfoItems as $infoItem)
                                            <div class="tf-product-info-liveview">
                                                <p>{{ $infoItem['label'] }}: <span class="fw-6">{{ $infoItem['value'] }}</span></p>
                                            </div>
                                        @endforeach
                                    @endif

                                    <div class="tf-product-info-variant-picker">
                                        @if ($colors->isNotEmpty())
                                            <div class="variant-picker-item">
                                                <div class="variant-picker-label">
                                                    {{ __('front.products.color') }}:
                                                    <span class="fw-6 variant-picker-label-value" data-detail-color-label>{{ $defaultColor['name'] ?? '' }}</span>
                                                </div>
                                                <div class="tf-product-info-code color-code">
                                                    <span class="label">{{ __('front.products.color_code') }}:</span>
                                                    <span class="value" data-detail-color-code>{{ $defaultColor['color_code'] ?? '' }}</span>
                                                </div>
                                                <div class="variant-picker-values" data-detail-colors>
                                                    @foreach ($colors as $index => $color)
                                                        @php($swatchStyle = trim((string) ($color['swatch_style'] ?? '')))
                                                        <input id="detail-color-{{ $index }}" type="radio" name="detail_color" value="{{ $color['name'] }}" data-color-index="{{ $index }}" @checked($index === $defaultColorIndex)>
                                                        <label class="hover-tooltip radius-60 color-btn" for="detail-color-{{ $index }}" data-value="{{ $color['name'] }}">
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
                                                        <label class="style-text" for="detail-size-{{ $index }}" data-value="{{ $size['value'] }}" @if (! empty($size['is_sold_out_normalized'])) aria-disabled="true" @endif>
                                                            <span class="size-label">{{ $size['value'] }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="tf-product-info-quantity">
                                        <div class="quantity-title fw-6">{{ __('front.products.quantity') }}</div>
                                        <div class="wg-quantity">
                                            <span class="btn-quantity btn-decrease" data-detail-qty="decrease">-</span>
                                            <input type="text" class="quantity-product" name="number" value="1" data-detail-quantity>
                                            <span class="btn-quantity btn-increase" data-detail-qty="increase">+</span>
                                        </div>
                                    </div>

                                    <div class="tf-product-info-buy-button">
                                        <form data-detail-cart-form data-cart-url="{{ $cartAddUrl }}">
                                            @csrf
                                            <button type="submit" class="tf-btn btn-fill justify-content-center fw-6 fs-16 flex-grow-1 animate-hover-btn btn-add-to-cart" data-detail-cart-submit @disabled(empty($cartAddUrl))>
                                                <span>{{ __('front.products.add_to_cart') }} -&nbsp;</span>
                                                <span class="tf-qty-price total-price js-currency-price" data-detail-submit-price data-base-price="{{ $product['price_current'] ?? $product['base_price'] ?? 0 }}" data-base-currency="{{ $product['base_currency'] ?? 'SYP' }}">{{ $product['price_current_label'] ?? $product['price_label'] ?? '' }}</span>
                                            </button>
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

        @if ($descriptionHtml !== '' || $specifications->isNotEmpty())
            <section class="flat-spacing-17 pt_0">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="widget-tabs style-has-border">
                                <ul class="widget-menu-tab">
                                    @if ($descriptionHtml !== '')
                                        <li class="item-title active"><span class="inner">{{ $isArabic ? 'وصف المنتج' : 'Description' }}</span></li>
                                    @endif
                                    @if ($specifications->isNotEmpty())
                                        <li class="item-title {{ $descriptionHtml === '' ? 'active' : '' }}"><span class="inner">{{ $isArabic ? 'المواصفات' : 'Additional Information' }}</span></li>
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
                    </div>
                </div>
            </section>
        @endif

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
