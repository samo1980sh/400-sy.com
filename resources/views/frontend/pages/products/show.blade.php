@extends('frontend.layouts.app')

@php
    $locale = $locale ?? app()->getLocale();
    $isArabic = $locale === 'ar';
    $product = $product ?? [];
    $relatedProducts = collect($related_products ?? [])->values();
    $colors = collect($product['colors'] ?? [])->values();
    $defaultColor = $colors->first() ?? [];
    $gallery = collect($defaultColor['gallery'] ?? ($product['gallery'] ?? []))
        ->filter()
        ->unique()
        ->values();
    $mainImage = $defaultColor['image'] ?? ($product['image'] ?? ($gallery->first() ?? ''));
    $sizeOptions = collect($defaultColor['size_options'] ?? ($product['size_options'] ?? []))->values();
    $defaultSize = $defaultColor['default_size'] ?? ($product['default_size'] ?? null);
    $cartAddUrl = $product['cart_add_url'] ?? null;
    $sizeChart = $product['size_chart'] ?? [];
    $hasSizeChart = !empty($product['has_size_chart']) && !empty($sizeChart['columns'] ?? []) && !empty($sizeChart['rows'] ?? []);
    $specifications = collect($product['specifications'] ?? [])->filter(fn ($item) => filled($item['label'] ?? null) && filled($item['value'] ?? null))->values();
    $description = trim((string) ($product['description'] ?? ''));
    $descriptionHtml = $description !== '' ? nl2br(e($description)) : '';
    $baseCategoryUrl = $product_model?->category?->slug ? route('front.category', $product_model->category->slug) : route('front.home');
    $backLabel = $product_model?->category
        ? ($isArabic
            ? ($product_model->category->title_ar ?: $product_model->category->title_en ?: __('front.products.products'))
            : ($product_model->category->title_en ?: $product_model->category->title_ar ?: __('front.products.products')))
        : __('front.products.products');
    $detailHighlights = collect([
        [
            'label' => $isArabic ? 'لون الفلترة' : 'Filter color',
            'value' => $product['filter_color_name'] ?? null,
        ],
        [
            'label' => 'Body Fit',
            'value' => $product['body_fit'] ?? null,
        ],
        [
            'label' => 'Drop',
            'value' => $product['drop_type'] ?? null,
        ],
        [
            'label' => $isArabic ? 'زمرة القياس' : 'Measurement group',
            'value' => $product['measurement_group'] ?? null,
        ],
        [
            'label' => $isArabic ? 'المنشأ' : 'Country',
            'value' => $product['country'] ?? null,
        ],
        [
            'label' => $isArabic ? 'المجموعة' : 'Collection',
            'value' => $product['collection'] ?? null,
        ],
    ])->filter(fn ($item) => filled($item['value'] ?? null))->take(4)->values();
    $relatedSectionTitle = ($product_model?->relationLoaded('complements') && $product_model->complements->isNotEmpty())
        ? ($isArabic ? 'منتجات مكملة' : 'Complementary products')
        : ($isArabic ? 'قد يعجبك أيضاً' : 'You may also like');
@endphp

@section('title', $product['title'] ?? ($page_title ?? __('front.brand')))
@section('meta_description', $description !== '' ? $description : ($product['title'] ?? __('front.brand')))

@push('styles')
    <style>
        .tf-main-product .row {
            --bs-gutter-x: 32px;
            align-items: flex-start;
        }

        .product-detail-gallery-thumb {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            background: #f7f7f7;
            padding: 0;
        }

        .product-detail-gallery-thumb.is-active {
            border-color: #111;
        }

        .product-detail-gallery-thumb img {
            width: 100%;
            aspect-ratio: 1 / 1.18;
            object-fit: cover;
            display: block;
        }

        .product-detail-main-image {
            width: 100%;
            border-radius: 18px;
            background: #f7f7f7;
            overflow: hidden;
            min-height: 640px;
        }

        .product-detail-main-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .product-detail-swatch.is-active .btn-checkbox,
        .product-detail-swatch input:checked + label .btn-checkbox {
            outline: 2px solid #111;
            outline-offset: 3px;
        }

        .product-detail-size-disabled {
            opacity: .45;
            pointer-events: none;
        }

        .product-detail-size-disabled span,
        .product-detail-size-disabled p {
            text-decoration: line-through;
        }

        .product-detail-meta {
            display: grid;
            gap: 8px;
        }

        .product-detail-meta__row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 14px;
        }

        .product-detail-meta__label {
            color: #6c6c6c;
        }

        .product-detail-meta__row a {
            text-decoration: none;
        }

        .product-detail-find-size {
            white-space: nowrap;
        }

        .product-detail-highlights {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .product-detail-highlights__item {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 14px;
            padding: 14px 16px;
            min-height: 88px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #fff;
        }

        .product-detail-highlights__label {
            color: #6c6c6c;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .product-detail-highlights__value {
            font-weight: 600;
            line-height: 1.45;
        }

        .product-detail-specs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 24px;
        }

        .product-detail-specs__item {
            border-bottom: 1px solid rgba(0, 0, 0, .08);
            padding-bottom: 12px;
        }

        .product-detail-specs__label {
            display: block;
            color: #6c6c6c;
            margin-bottom: 4px;
        }

        @media (max-width: 991.98px) {
            .product-detail-main-image {
                min-height: 0;
            }
        }

        @media (max-width: 767.98px) {
            .tf-main-product .row {
                --bs-gutter-x: 18px;
            }

            .product-detail-highlights {
                grid-template-columns: 1fr;
            }

            .product-detail-specs {
                grid-template-columns: 1fr;
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
                        <a href="{{ $baseCategoryUrl }}" class="tf-breadcrumb-back hover-tooltip center">
                            <i class="icon icon-shop"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <section class="flat-spacing-4 pt_0">
            <div class="tf-main-product section-image-zoom">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="tf-product-media-wrap sticky-top">
                                <div class="thumbs-slider">
                                    <div class="row g-3">
                                        <div class="col-3">
                                            <div class="d-grid gap-3" data-detail-thumbs>
                                                @forelse ($gallery as $index => $image)
                                                    <button type="button" class="product-detail-gallery-thumb {{ $index === 0 ? 'is-active' : '' }}" data-detail-thumb="{{ $image }}">
                                                        <img class="lazyload" data-src="{{ $image }}" src="{{ $image }}" alt="{{ $product['title'] ?? '' }}">
                                                    </button>
                                                @empty
                                                    @if ($mainImage)
                                                        <button type="button" class="product-detail-gallery-thumb is-active" data-detail-thumb="{{ $mainImage }}">
                                                            <img class="lazyload" data-src="{{ $mainImage }}" src="{{ $mainImage }}" alt="{{ $product['title'] ?? '' }}">
                                                        </button>
                                                    @endif
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="col-9">
                                            <div class="product-detail-main-image">
                                                <img data-detail-main-image class="lazyload" data-src="{{ $mainImage }}" src="{{ $mainImage }}" alt="{{ $product['title'] ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="tf-product-info-wrap position-relative">
                                <div class="tf-zoom-main"></div>
                                <div class="tf-product-info-list other-image-zoom">
                                    <div class="tf-product-info-title">
                                        <h5>{{ $product['title'] ?? '' }}</h5>
                                    </div>

                                    @if (!empty($product['badge']))
                                        <div class="tf-product-info-badges">
                                            <span class="badge {{ $product['badge_class'] ?? '' }}">{{ $product['badge'] }}</span>
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

                                    <div class="product-detail-meta mb_24">
                                        @if (!empty($product['product_code']))
                                            <div class="product-detail-meta__row tf-product-info-liveview">
                                                <span class="product-detail-meta__label">{{ __('front.products.product_code') }}:</span>
                                                <span class="fw-6">{{ $product['product_code'] }}</span>
                                            </div>
                                        @endif

                                        <div class="product-detail-meta__row tf-product-info-liveview">
                                            <span class="product-detail-meta__label">{{ $isArabic ? 'القسم' : 'Category' }}:</span>
                                            <a href="{{ $baseCategoryUrl }}" class="fw-6 link">{{ $backLabel }}</a>
                                        </div>

                                        @if (!empty($product['display_color_description']))
                                            <div class="product-detail-meta__row tf-product-info-liveview">
                                                <span class="product-detail-meta__label">{{ $isArabic ? 'لون المنتج المعروض' : 'Displayed color' }}:</span>
                                                <span class="fw-6">{{ $product['display_color_description'] }}</span>
                                            </div>
                                        @endif

                                    </div>

                                    @if ($descriptionHtml !== '')
                                        <div class="tf-product-description mb_24">
                                            <p>{!! $descriptionHtml !!}</p>
                                        </div>
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
                                                        @php
                                                            $colorValue = $color['name'] ?? ('color-' . $index);
                                                            $swatchStyle = trim((string) ($color['swatch_style'] ?? ''));
                                                        @endphp
                                                        <div class="product-detail-swatch {{ $index === 0 ? 'is-active' : '' }}">
                                                            <input type="radio" name="detail_color" id="detail-color-{{ $index }}" value="{{ $colorValue }}" data-color-index="{{ $index }}" @checked($index === 0)>
                                                            <label class="hover-tooltip radius-60" for="detail-color-{{ $index }}" data-value="{{ $colorValue }}">
                                                                <span class="btn-checkbox {{ $color['class_name'] ?? 'four-Black' }}" @if ($swatchStyle !== '') style="{{ $swatchStyle }}" @endif></span>
                                                                <span class="tooltip">{{ $colorValue }}</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="variant-picker-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="variant-picker-label">
                                                    {{ __('front.products.size') }}:
                                                    <span class="fw-6 variant-picker-label-value" data-detail-size-label>{{ $defaultSize ?? '' }}</span>
                                                </div>
                                                @if ($hasSizeChart)
                                                    <a href="#find_size" data-bs-toggle="modal" class="find-size fw-6 product-detail-find-size">{{ __('front.products.find_your_size') }}</a>
                                                @endif
                                            </div>
                                            <div class="variant-picker-values" data-detail-sizes>
                                                @foreach ($sizeOptions as $index => $size)
                                                    @php
                                                        $sizeValue = $size['size'] ?? ($size['name'] ?? ($size['label'] ?? ''));
                                                        $soldOut = !empty($size['is_sold_out']) || (($size['available'] ?? true) === false);
                                                    @endphp
                                                    @if ($sizeValue !== '')
                                                        <input type="radio" name="detail_size" id="detail-size-{{ $index }}" value="{{ $sizeValue }}" data-size-index="{{ $index }}" data-size-id="{{ $size['size_id'] ?? '' }}" data-size-code="{{ $size['size_code'] ?? '' }}" data-variant-id="{{ $size['variant_id'] ?? '' }}" data-product-color-id="{{ $size['product_color_id'] ?? '' }}" @checked($sizeValue === $defaultSize && ! $soldOut) @disabled($soldOut)>
                                                        <label class="style-text size-btn {{ $soldOut ? 'product-detail-size-disabled' : '' }}" for="detail-size-{{ $index }}" data-value="{{ $sizeValue }}">
                                                            <p>{{ $sizeValue }}</p>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
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
                                                <span class="tf-qty-price total-price" data-detail-submit-price>{{ $product['price_current_label'] ?? $product['price_label'] ?? '' }}</span>
                                            </button>
                                            <a href="javascript:void(0);" class="tf-product-btn-wishlist hover-tooltip box-icon bg_white wishlist btn-icon-action">
                                                <span class="icon icon-heart"></span>
                                                <span class="tooltip">{{ __('front.products.add_to_wishlist') }}</span>
                                                <span class="icon icon-delete"></span>
                                            </a>
                                        </form>
                                    </div>

                                    @if ($detailHighlights->isNotEmpty())
                                        <div class="product-detail-highlights">
                                            @foreach ($detailHighlights as $spec)
                                                <div class="product-detail-highlights__item">
                                                    <span class="product-detail-highlights__label">{{ $spec['label'] }}</span>
                                                    <div class="product-detail-highlights__value">{{ $spec['value'] }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($descriptionHtml !== '' || $specifications->isNotEmpty() || $hasSizeChart)
            <section class="flat-spacing-1 pt_0">
                <div class="container">
                    <div class="widget-tabs style-has-border">
                        <ul class="widget-menu-tab">
                            @if ($descriptionHtml !== '')
                                <li class="item-title active">
                                    <span class="inner">{{ $isArabic ? 'وصف المنتج' : 'Product description' }}</span>
                                </li>
                            @endif

                            @if ($specifications->isNotEmpty())
                                <li class="item-title {{ $descriptionHtml === '' ? 'active' : '' }}">
                                    <span class="inner">{{ $isArabic ? 'المواصفات' : 'Specifications' }}</span>
                                </li>
                            @endif

                            @if ($hasSizeChart)
                                <li class="item-title {{ $descriptionHtml === '' && $specifications->isEmpty() ? 'active' : '' }}">
                                    <span class="inner">{{ __('front.products.size_chart') }}</span>
                                </li>
                            @endif
                        </ul>

                        <div class="widget-content-tab">
                            @if ($descriptionHtml !== '')
                                <div class="widget-content-inner active">
                                    <div class="tab-description">
                                        <p>{!! $descriptionHtml !!}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($specifications->isNotEmpty())
                                <div class="widget-content-inner {{ $descriptionHtml === '' ? 'active' : '' }}">
                                    <div class="product-detail-specs">
                                        @foreach ($specifications as $spec)
                                            <div class="product-detail-specs__item">
                                                <span class="product-detail-specs__label">{{ $spec['label'] }}</span>
                                                <strong>{{ $spec['value'] }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($hasSizeChart)
                                <div class="widget-content-inner {{ $descriptionHtml === '' && $specifications->isEmpty() ? 'active' : '' }}">
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    @foreach (($sizeChart['columns'] ?? []) as $column)
                                                        <th>{{ $column['label'] ?? '' }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (($sizeChart['rows'] ?? []) as $row)
                                                    <tr>
                                                        @foreach (($sizeChart['columns'] ?? []) as $column)
                                                            <td>{{ data_get($row, $column['key'] ?? '', '') }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($relatedProducts->isNotEmpty())
            <section class="flat-spacing-1 pt_0">
                <div class="container">
                    <div class="flat-title">
                        <span class="title">{{ $relatedSectionTitle }}</span>
                    </div>
                    <div class="grid-layout wrapper-shop" data-grid="grid-4">
                        @foreach ($relatedProducts as $relatedProduct)
                            @include('frontend.partials.product-card', [
                                'product' => $relatedProduct,
                                'loadmore_hidden' => false,
                            ])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>

    @include('frontend.partials.footer', [
        'contact' => $contact ?? null,
        'socialLinks' => $social_links ?? [],
        'footerPages' => $footer_pages ?? [],
        'collections' => $collections ?? [],
    ])

    @include('frontend.partials.toolbar-bottom', [
        'cartCount' => $cart_count ?? 0,
    ])
    @include('frontend.partials.mobile-menu', [
        'navCategories' => $nav_categories ?? [],
        'quickLinks' => $quick_links ?? [],
    ])
    @include('frontend.partials.search-canvas', [
        'quickLinks' => $quick_links ?? [],
    ])
    @include('frontend.partials.shopping-cart', [
        'cartState' => $cart_state ?? [],
    ])
    @include('frontend.partials.auth-modals')
    @include('frontend.partials.quick-add')
    @include('frontend.partials.quick-view')
    @include('frontend.partials.find-size')
@endsection

@push('scripts')
    @include('frontend.partials.product-scripts')

    <script>
        (function () {
            const product = @json($product);
            const form = document.querySelector('[data-detail-cart-form]');

            if (!form || !product) {
                return;
            }

            const colors = Array.isArray(product.colors) ? product.colors : [];
            let currentColorIndex = 0;
            let currentSizeIndex = 0;

            const mainImage = document.querySelector('[data-detail-main-image]');
            const thumbsWrap = document.querySelector('[data-detail-thumbs]');
            const sizesWrap = document.querySelector('[data-detail-sizes]');
            const colorLabel = document.querySelector('[data-detail-color-label]');
            const colorCode = document.querySelector('[data-detail-color-code]');
            const sizeLabel = document.querySelector('[data-detail-size-label]');
            const currentPrice = document.querySelector('[data-detail-current-price]');
            const comparePrice = document.querySelector('[data-detail-compare-price]');
            const submitPrice = document.querySelector('[data-detail-submit-price]');
            const quantityInput = document.querySelector('[data-detail-quantity]');
            const submitButton = document.querySelector('[data-detail-cart-submit]');

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
                });
            }

            function activeColor() {
                return colors[currentColorIndex] || colors[0] || {};
            }

            function activeSizeOptions() {
                const color = activeColor();
                return Array.isArray(color.size_options) && color.size_options.length
                    ? color.size_options
                    : (Array.isArray(product.size_options) ? product.size_options : []);
            }

            function isSoldOut(size) {
                return size && (size.is_sold_out === true || size.available === false || Number(size.quantity || 0) <= 0);
            }

            function formatGallery(color) {
                const gallery = Array.isArray(color.gallery) && color.gallery.length
                    ? color.gallery
                    : (color.image ? [color.image] : (Array.isArray(product.gallery) ? product.gallery : []));

                return gallery.filter(Boolean);
            }

            function setMainImage(src) {
                if (!mainImage || !src) {
                    return;
                }

                mainImage.src = src;
                mainImage.setAttribute('data-src', src);
            }

            function firstAvailableSizeIndex(sizes) {
                for (let i = 0; i < sizes.length; i++) {
                    if (!isSoldOut(sizes[i])) {
                        return i;
                    }
                }

                return -1;
            }

            function renderGallery(color) {
                const gallery = formatGallery(color);

                if (gallery.length) {
                    setMainImage(gallery[0]);
                }

                if (!thumbsWrap) {
                    return;
                }

                thumbsWrap.innerHTML = gallery.map(function (image, index) {
                    return '<button type="button" class="product-detail-gallery-thumb ' + (index === 0 ? 'is-active' : '') + '" data-detail-thumb="' + escapeHtml(image) + '">' +
                        '<img class="lazyload" data-src="' + escapeHtml(image) + '" src="' + escapeHtml(image) + '" alt="' + escapeHtml(product.title || '') + '">' +
                        '</button>';
                }).join('');
            }

            function renderSizes(color) {
                const sizes = activeSizeOptions();
                currentSizeIndex = 0;

                if (!sizesWrap) {
                    return;
                }

                sizesWrap.innerHTML = sizes.map(function (size, index) {
                    const value = size.size || size.name || size.label || size.value || '';
                    const soldOut = isSoldOut(size);
                    const checked = !soldOut && index === firstAvailableSizeIndex(sizes) ? ' checked' : '';
                    const disabled = soldOut ? ' disabled' : '';
                    const cls = soldOut ? ' product-detail-size-disabled' : '';

                    return '<input type="radio" name="detail_size" id="detail-size-js-' + index + '" value="' + escapeHtml(value) + '" data-size-index="' + index + '" data-size-id="' + escapeHtml(size.size_id || '') + '" data-size-code="' + escapeHtml(size.size_code || '') + '" data-variant-id="' + escapeHtml(size.variant_id || '') + '" data-product-color-id="' + escapeHtml(size.product_color_id || color.id || '') + '"' + checked + disabled + '>' +
                        '<label class="style-text size-btn' + cls + '" for="detail-size-js-' + index + '" data-value="' + escapeHtml(value) + '"><p>' + escapeHtml(value) + '</p></label>';
                }).join('');

                const selectedInput = sizesWrap.querySelector('input[name="detail_size"]:checked');
                currentSizeIndex = selectedInput ? Number(selectedInput.dataset.sizeIndex || 0) : 0;
            }

            function updatePricing() {
                const color = activeColor();
                const sizes = activeSizeOptions();
                const size = sizes[currentSizeIndex] || null;
                const priceLabel = (size && size.price_current_label) || color.price_current_label || product.price_current_label || product.price_label || '';
                const compareLabel = (size && size.compare_price_label) || color.compare_price_label || product.compare_price_label || '';
                const basePrice = (size && size.price_current) || color.price_current || product.price_current || product.base_price || 0;
                const compareBase = (size && size.compare_price) || color.compare_price || product.compare_price || 0;
                const currency = product.base_currency || 'SYP';

                if (currentPrice) {
                    currentPrice.textContent = priceLabel;
                    currentPrice.setAttribute('data-base-price', basePrice || 0);
                    currentPrice.setAttribute('data-base-currency', currency);
                }

                if (submitPrice) {
                    submitPrice.textContent = priceLabel;
                }

                if (comparePrice) {
                    comparePrice.textContent = compareLabel || '';
                    comparePrice.setAttribute('data-base-price', compareBase || 0);
                    comparePrice.setAttribute('data-base-currency', currency);
                    comparePrice.classList.toggle('d-none', !compareLabel);
                }

                if (window.updateCurrencyConvertedPrices) {
                    window.updateCurrencyConvertedPrices();
                }
            }

            function updateLabels() {
                const color = activeColor();
                const sizes = activeSizeOptions();
                const size = sizes[currentSizeIndex] || null;

                if (colorLabel) {
                    colorLabel.textContent = color.name || '';
                }

                if (colorCode) {
                    colorCode.textContent = color.color_code || '';
                }

                if (sizeLabel) {
                    sizeLabel.textContent = size ? (size.size || size.name || size.label || '') : '';
                }
            }

            function selectedSizeInput() {
                return document.querySelector('[data-detail-sizes] input[name="detail_size"]:checked');
            }

            function selectColor(index) {
                currentColorIndex = Number(index || 0);
                const color = activeColor();

                document.querySelectorAll('[data-detail-colors] .product-detail-swatch').forEach(function (item) {
                    item.classList.remove('is-active');
                });

                const input = document.querySelector('[data-detail-colors] input[data-color-index="' + currentColorIndex + '"]');
                if (input) {
                    input.checked = true;
                    input.closest('.product-detail-swatch')?.classList.add('is-active');
                }

                renderGallery(color);
                renderSizes(color);
                updateLabels();
                updatePricing();
            }

            function updateCartState(response) {
                if (!response) {
                    return;
                }

                const count = response.cart_state && typeof response.cart_state.count !== 'undefined'
                    ? response.cart_state.count
                    : 0;

                document.querySelectorAll('[data-cart-count]').forEach(function (item) {
                    item.textContent = count;
                });

                if (response.cart_html) {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = response.cart_html;
                    const newCart = wrapper.querySelector('#shoppingCart');
                    const currentCart = document.querySelector('#shoppingCart');

                    if (newCart && currentCart) {
                        const newItems = newCart.querySelector('[data-cart-items]');
                        const items = currentCart.querySelector('[data-cart-items]');
                        const newSubtotal = newCart.querySelector('[data-cart-subtotal]');
                        const subtotal = currentCart.querySelector('[data-cart-subtotal]');

                        if (newItems && items) {
                            items.innerHTML = newItems.innerHTML;
                        }

                        if (newSubtotal && subtotal) {
                            subtotal.textContent = newSubtotal.textContent;
                            subtotal.setAttribute('data-base-price', newSubtotal.getAttribute('data-base-price') || 0);
                            subtotal.setAttribute('data-base-currency', newSubtotal.getAttribute('data-base-currency') || product.base_currency || 'SYP');
                        }
                    }
                }

                if (window.updateCurrencyConvertedPrices) {
                    window.updateCurrencyConvertedPrices();
                }
            }

            function renderSizeChartModal() {
                if (!window.jQuery) {
                    return;
                }

                const $modal = window.jQuery('#find_size');
                const chart = product && product.size_chart ? product.size_chart : {};
                const rows = Array.isArray(chart.rows) ? chart.rows : [];
                const columns = Array.isArray(chart.columns) ? chart.columns : [];
                const $table = $modal.find('[data-size-chart-table]');
                const $head = $modal.find('[data-size-chart-head]');
                const $body = $modal.find('[data-size-chart-body]');
                const $empty = $modal.find('[data-size-chart-empty]');
                const $guideWrap = $modal.find('[data-size-chart-guide-wrap]');
                const $guideImage = $modal.find('[data-size-chart-guide-image]');
                const $tableWrap = $modal.find('[data-size-chart-table-wrap]');
                const guideImage = String(chart.guide_image || '').trim();

                $modal.find('[data-size-chart-title]').text(chart.title || '');
                $modal.find('[data-size-chart-subtitle]').text(chart.subtitle || '');

                if (guideImage) {
                    $guideImage.attr('src', guideImage);
                    $guideWrap.removeClass('d-none');
                    $tableWrap.removeClass('col-lg-12').addClass('col-lg-8');
                } else {
                    $guideImage.attr('src', '');
                    $guideWrap.addClass('d-none');
                    $tableWrap.removeClass('col-lg-8').addClass('col-lg-12');
                }

                if (!rows.length || !columns.length) {
                    $table.addClass('d-none');
                    $empty.removeClass('d-none');
                    $head.empty();
                    $body.empty();
                    return;
                }

                let headHtml = '';
                columns.forEach(function (column) {
                    headHtml += '<th>' + escapeHtml(column.label || '') + '</th>';
                });

                let bodyHtml = '';
                rows.forEach(function (row) {
                    bodyHtml += '<tr>';
                    columns.forEach(function (column) {
                        const value = row[column.key] ?? '';
                        bodyHtml += '<td>' + escapeHtml(value === null || value === undefined || value === '' ? '-' : String(value)) + '</td>';
                    });
                    bodyHtml += '</tr>';
                });

                $head.html(headHtml);
                $body.html(bodyHtml);
                $empty.addClass('d-none');
                $table.removeClass('d-none');
            }

            document.addEventListener('change', function (event) {
                const colorInput = event.target.closest('[data-detail-colors] input[name="detail_color"]');
                if (colorInput) {
                    selectColor(colorInput.dataset.colorIndex || 0);
                    return;
                }

                const sizeInput = event.target.closest('[data-detail-sizes] input[name="detail_size"]');
                if (sizeInput) {
                    currentSizeIndex = Number(sizeInput.dataset.sizeIndex || 0);
                    updateLabels();
                    updatePricing();
                }
            });

            document.addEventListener('click', function (event) {
                const thumb = event.target.closest('[data-detail-thumb]');
                if (thumb) {
                    event.preventDefault();
                    thumbsWrap?.querySelectorAll('.product-detail-gallery-thumb').forEach(function (item) {
                        item.classList.remove('is-active');
                    });
                    thumb.classList.add('is-active');
                    setMainImage(thumb.dataset.detailThumb || '');
                    return;
                }

                const qtyButton = event.target.closest('[data-detail-qty]');
                if (qtyButton && quantityInput) {
                    const current = parseInt(quantityInput.value, 10) || 1;
                    quantityInput.value = qtyButton.dataset.detailQty === 'decrease'
                        ? Math.max(1, current - 1)
                        : Math.min(99, current + 1);
                    return;
                }

                const sizeChartTrigger = event.target.closest('a.find-size');
                if (sizeChartTrigger) {
                    renderSizeChartModal();
                }
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const url = form.getAttribute('data-cart-url') || '';
                if (!url || !submitButton) {
                    return;
                }

                const color = activeColor();
                const sizeInput = selectedSizeInput();
                const quantity = parseInt(quantityInput?.value || '1', 10) || 1;
                const formData = new FormData();

                formData.append('quantity', String(Math.max(1, Math.min(99, quantity))));
                formData.append('color', color.name || '');
                formData.append('color_name', color.name || '');
                formData.append('color_id', color.id || '');
                formData.append('color_code', color.color_code || '');

                if (sizeInput) {
                    formData.append('size', sizeInput.value || '');
                    formData.append('size_id', sizeInput.dataset.sizeId || '');
                    formData.append('size_code', sizeInput.dataset.sizeCode || '');
                    formData.append('variant_id', sizeInput.dataset.variantId || '');
                }

                submitButton.disabled = true;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Cart request failed');
                        }

                        return response.json();
                    })
                    .then(function (response) {
                        updateCartState(response);
                        const cart = document.getElementById('shoppingCart');
                        if (cart && window.bootstrap && window.bootstrap.Modal) {
                            window.bootstrap.Modal.getOrCreateInstance(cart).show();
                        }
                    })
                    .catch(function () {
                        alert(@json($isArabic ? 'تعذر إضافة المنتج إلى السلة. حاول مرة أخرى.' : 'Could not add the product to cart. Please try again.'));
                    })
                    .finally(function () {
                        submitButton.disabled = false;
                    });
            });

            selectColor(0);
            renderSizeChartModal();
        })();
    </script>
@endpush
