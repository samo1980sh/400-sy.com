@extends('frontend.layouts.app')

@php
    $locale = $locale ?? app()->getLocale();
    $isArabic = $locale === 'ar';
    $product = $product ?? [];
    $productModel = $product_model ?? null;
    $relatedProducts = collect($related_products ?? [])->values();
    $colors = collect($product['colors'] ?? [])->filter(fn ($color) => filled($color['name'] ?? null))->values();
    $defaultColor = $colors->first() ?? [];
    $defaultGallery = collect($defaultColor['gallery'] ?? [])
        ->filter()
        ->values();
    $fallbackGallery = collect($product['gallery'] ?? [])
        ->filter()
        ->values();
    $gallery = $defaultGallery->isNotEmpty() ? $defaultGallery : $fallbackGallery;
    $mainImage = $defaultColor['image'] ?? ($product['image'] ?? ($gallery->first() ?? ''));
    $sizeOptions = collect($defaultColor['size_options'] ?? ($product['size_options'] ?? []))
        ->filter(fn ($size) => filled($size['size'] ?? ($size['name'] ?? ($size['label'] ?? null))))
        ->values();
    $defaultSize = $defaultColor['default_size'] ?? ($product['default_size'] ?? null);
    $cartAddUrl = $product['cart_add_url'] ?? null;
    $description = trim((string) ($product['description'] ?? ''));
    $descriptionHtml = $description !== '' ? nl2br(e($description)) : '';
    $sizeChart = $product['size_chart'] ?? [];
    $hasSizeChart = !empty($product['has_size_chart']) && !empty($sizeChart['columns'] ?? []) && !empty($sizeChart['rows'] ?? []);
    $specifications = collect($product['specifications'] ?? [])
        ->filter(fn ($item) => filled($item['label'] ?? null) && filled($item['value'] ?? null))
        ->values();
    $categoryUrl = $productModel?->category?->slug ? route('front.category', $productModel->category->slug) : route('front.home');
    $relatedTitle = ($productModel?->relationLoaded('complements') && $productModel->complements->isNotEmpty())
        ? ($isArabic ? 'منتجات مكملة' : 'Complementary products')
        : ($isArabic ? 'قد يعجبك أيضاً' : 'You may also like');
@endphp

@section('title', $product['title'] ?? ($page_title ?? __('front.brand')))
@section('meta_description', $description !== '' ? $description : ($product['title'] ?? __('front.brand')))

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
                        <a href="{{ $categoryUrl }}" class="tf-breadcrumb-back hover-tooltip center">
                            <i class="icon icon-shop"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <section class="flat-spacing-4 pt_0">
            <div class="tf-main-product section-image-zoom" data-detail-product='@json($product)'>
                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="tf-product-media-wrap sticky-top">
                                <div class="thumbs-slider">
                                    <div dir="ltr" class="swiper tf-product-media-thumbs other-image-zoom" data-direction="vertical" data-detail-thumbs-swiper>
                                        <div class="swiper-wrapper stagger-wrap" data-detail-thumbs>
                                            @foreach ($gallery as $index => $image)
                                                <div class="swiper-slide stagger-item" data-color="{{ $defaultColor['name'] ?? '' }}">
                                                    <div class="item">
                                                        <img class="lazyload" data-src="{{ $image }}" src="{{ $image }}" alt="{{ $product['title'] ?? '' }}">
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div dir="ltr" class="swiper tf-product-media-main" data-detail-main-swiper>
                                        <div class="swiper-wrapper" data-detail-gallery>
                                            @foreach ($gallery as $image)
                                                <div class="swiper-slide" data-color="{{ $defaultColor['name'] ?? '' }}">
                                                    <a href="{{ $image }}" target="_blank" class="item" data-pswp-width="770px" data-pswp-height="1075px">
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

                                    @if (!empty($product['badge']))
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

                                    @if (!empty($product['product_code']))
                                        <div class="tf-product-info-liveview">
                                            <p>{{ __('front.products.product_code') }}: <span class="fw-6">{{ $product['product_code'] }}</span></p>
                                        </div>
                                    @endif

                                    @if (!empty($product['category_name']))
                                        <div class="tf-product-info-liveview">
                                            <p>{{ $isArabic ? 'القسم' : 'Category' }}: <a href="{{ $categoryUrl }}" class="fw-6 link">{{ $product['category_name'] }}</a></p>
                                        </div>
                                    @endif

                                    @if (!empty($product['display_color_description']))
                                        <div class="tf-product-info-liveview">
                                            <p>{{ $isArabic ? 'لون المنتج المعروض' : 'Displayed color' }}: <span class="fw-6">{{ $product['display_color_description'] }}</span></p>
                                        </div>
                                    @endif

                                    @if ($descriptionHtml !== '')
                                        <div class="tf-product-description">
                                            <p>{!! $descriptionHtml !!}</p>
                                        </div>
                                    @endif

                                    <div class="tf-product-info-variant-picker">
                                        @if ($colors->isNotEmpty())
                                            <div class="variant-picker-item">
                                                <div class="variant-picker-label">
                                                    {{ __('front.products.color') }}: <span class="fw-6 variant-picker-label-value" data-detail-color-label>{{ $defaultColor['name'] ?? '' }}</span>
                                                </div>
                                                <div class="tf-product-info-code color-code">
                                                    <span class="label">{{ __('front.products.color_code') }}:</span>
                                                    <span class="value" data-detail-color-code>{{ $defaultColor['color_code'] ?? '' }}</span>
                                                </div>
                                                <div class="variant-picker-values" data-detail-colors>
                                                    @foreach ($colors as $index => $color)
                                                        @php
                                                            $colorName = $color['name'] ?? ('color-' . $index);
                                                            $swatchStyle = trim((string) ($color['swatch_style'] ?? ''));
                                                        @endphp
                                                        <input id="detail-color-{{ $index }}" type="radio" name="detail_color" value="{{ $colorName }}" data-color-index="{{ $index }}" @checked($index === 0)>
                                                        <label class="hover-tooltip radius-60 color-btn" for="detail-color-{{ $index }}" data-value="{{ $colorName }}">
                                                            <span class="btn-checkbox {{ $color['class_name'] ?? 'four-Black' }}" @if ($swatchStyle !== '') style="{{ $swatchStyle }}" @endif></span>
                                                            <span class="tooltip">{{ $colorName }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="variant-picker-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="variant-picker-label">
                                                    {{ __('front.products.size') }}: <span class="fw-6 variant-picker-label-value" data-detail-size-label>{{ $defaultSize ?? '' }}</span>
                                                </div>
                                                @if ($hasSizeChart)
                                                    <a href="#find_size" data-bs-toggle="modal" class="find-size fw-6" data-detail-find-size>{{ __('front.products.find_your_size') }}</a>
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
                                                        <label class="style-text size-btn" for="detail-size-{{ $index }}" data-value="{{ $sizeValue }}" @if ($soldOut) aria-disabled="true" @endif>
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

                                    @if ($specifications->take(4)->isNotEmpty())
                                        <div class="tf-product-info-delivery-return">
                                            <div class="row">
                                                @foreach ($specifications->take(4) as $spec)
                                                    <div class="col-xl-6 col-12">
                                                        <div class="tf-product-delivery {{ $loop->last && $loop->even ? 'mb-0' : '' }}">
                                                            <div class="inner">
                                                                <p>{{ $spec['label'] }}</p>
                                                                <span>{{ $spec['value'] }}</span>
                                                            </div>
                                                        </div>
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
            </div>
        </section>

        @if ($descriptionHtml !== '' || $specifications->isNotEmpty() || $hasSizeChart)
            <section class="flat-spacing-17 pt_0">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="widget-tabs style-has-border">
                                <ul class="widget-menu-tab">
                                    @if ($descriptionHtml !== '')
                                        <li class="item-title active">
                                            <span class="inner">{{ $isArabic ? 'وصف المنتج' : 'Description' }}</span>
                                        </li>
                                    @endif
                                    @if ($specifications->isNotEmpty())
                                        <li class="item-title {{ $descriptionHtml === '' ? 'active' : '' }}">
                                            <span class="inner">{{ $isArabic ? 'المواصفات' : 'Additional information' }}</span>
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
                                            <div class="tf-page-privacy-policy">
                                                @foreach ($specifications as $spec)
                                                    <div class="d-flex justify-content-between flex-wrap gap-3 py-3 border-bottom">
                                                        <div class="fw-6">{{ $spec['label'] }}</div>
                                                        <div>{{ $spec['value'] }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($hasSizeChart)
                                        <div class="widget-content-inner {{ $descriptionHtml === '' && $specifications->isEmpty() ? 'active' : '' }}">
                                            <div class="tab-description">
                                                <a href="#find_size" data-bs-toggle="modal" class="tf-btn btn-line">
                                                    {{ __('front.products.size_chart') }}
                                                    <i class="icon icon-arrow1-top-left"></i>
                                                </a>
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
                    <div class="flat-title">
                        <span class="title">{{ $relatedTitle }}</span>
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
        (function ($) {
            var $root = $('[data-detail-product]').first();

            if (!$root.length) {
                return;
            }

            var product = $root.data('detail-product') || {};
            var colors = Array.isArray(product.colors) ? product.colors : [];
            var currentColorIndex = 0;
            var currentSizeIndex = 0;
            var $form = $('[data-detail-cart-form]');
            var $qtyInput = $('[data-detail-quantity]');
            var $submit = $('[data-detail-cart-submit]');
            var $currentPrice = $('[data-detail-current-price]');
            var $comparePrice = $('[data-detail-compare-price]');
            var $submitPrice = $('[data-detail-submit-price]');
            var $colorLabel = $('[data-detail-color-label]');
            var $colorCode = $('[data-detail-color-code]');
            var $sizeLabel = $('[data-detail-size-label]');
            var thumbsSwiper = null;
            var mainSwiper = null;

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
                });
            }

            function initSwipers() {
                if (typeof Swiper === 'undefined') {
                    return;
                }

                if (thumbsSwiper && typeof thumbsSwiper.destroy === 'function') {
                    thumbsSwiper.destroy(true, true);
                }

                if (mainSwiper && typeof mainSwiper.destroy === 'function') {
                    mainSwiper.destroy(true, true);
                }

                var thumbsEl = document.querySelector('[data-detail-thumbs-swiper]');
                var mainEl = document.querySelector('[data-detail-main-swiper]');

                if (!thumbsEl || !mainEl) {
                    return;
                }

                thumbsSwiper = new Swiper(thumbsEl, {
                    direction: 'vertical',
                    slidesPerView: 5,
                    spaceBetween: 12,
                    watchSlidesProgress: true,
                });

                mainSwiper = new Swiper(mainEl, {
                    slidesPerView: 1,
                    spaceBetween: 0,
                    thumbs: { swiper: thumbsSwiper },
                    navigation: {
                        nextEl: mainEl.querySelector('.swiper-button-next'),
                        prevEl: mainEl.querySelector('.swiper-button-prev'),
                    },
                });
            }

            function activeColor() {
                return colors[currentColorIndex] || colors[0] || {};
            }

            function activeSizeOptions() {
                var color = activeColor();
                return Array.isArray(color.size_options) && color.size_options.length
                    ? color.size_options
                    : (Array.isArray(product.size_options) ? product.size_options : []);
            }

            function isSoldOut(size) {
                return size && (size.is_sold_out === true || size.available === false || Number(size.quantity || 0) <= 0);
            }

            function firstAvailableSizeIndex(items) {
                for (var i = 0; i < items.length; i++) {
                    if (!isSoldOut(items[i])) {
                        return i;
                    }
                }

                return -1;
            }

            function galleryForColor(color) {
                var gallery = Array.isArray(color.gallery) && color.gallery.length
                    ? color.gallery
                    : (color.image ? [color.image] : (Array.isArray(product.gallery) ? product.gallery : []));

                return gallery.filter(Boolean);
            }

            function renderGallery(color) {
                var gallery = galleryForColor(color);
                var colorKey = color.name || '';
                var thumbsHtml = '';
                var mainHtml = '';

                gallery.forEach(function (image) {
                    thumbsHtml += '<div class="swiper-slide stagger-item" data-color="' + escapeHtml(colorKey) + '"><div class="item"><img class="lazyload" data-src="' + escapeHtml(image) + '" src="' + escapeHtml(image) + '" alt="' + escapeHtml(product.title || '') + '"></div></div>';
                    mainHtml += '<div class="swiper-slide" data-color="' + escapeHtml(colorKey) + '"><a href="' + escapeHtml(image) + '" target="_blank" class="item" data-pswp-width="770px" data-pswp-height="1075px"><img class="tf-image-zoom lazyload" data-zoom="' + escapeHtml(image) + '" data-src="' + escapeHtml(image) + '" src="' + escapeHtml(image) + '" alt="' + escapeHtml(product.title || '') + '"></a></div>';
                });

                $('[data-detail-thumbs]').html(thumbsHtml);
                $('[data-detail-gallery]').html(mainHtml);
                initSwipers();
            }

            function renderSizes(color) {
                var sizes = activeSizeOptions();
                var firstAvailable = firstAvailableSizeIndex(sizes);
                var html = '';

                sizes.forEach(function (size, index) {
                    var value = size.size || size.name || size.label || '';
                    var soldOut = isSoldOut(size);
                    var checked = index === firstAvailable && !soldOut ? ' checked' : '';
                    var disabled = soldOut ? ' disabled' : '';

                    if (!value) {
                        return;
                    }

                    html += '<input type="radio" name="detail_size" id="detail-size-js-' + index + '" value="' + escapeHtml(value) + '" data-size-index="' + index + '" data-size-id="' + escapeHtml(size.size_id || '') + '" data-size-code="' + escapeHtml(size.size_code || '') + '" data-variant-id="' + escapeHtml(size.variant_id || '') + '" data-product-color-id="' + escapeHtml(size.product_color_id || '') + '"' + checked + disabled + '>';
                    html += '<label class="style-text size-btn" for="detail-size-js-' + index + '" data-value="' + escapeHtml(value) + '"' + (soldOut ? ' aria-disabled="true"' : '') + '><p>' + escapeHtml(value) + '</p></label>';
                });

                $('[data-detail-sizes]').html(html);
                currentSizeIndex = firstAvailable >= 0 ? firstAvailable : 0;
            }

            function updateLabels() {
                var color = activeColor();
                var sizes = activeSizeOptions();
                var size = sizes[currentSizeIndex] || null;

                $colorLabel.text(color.name || '');
                $colorCode.text(color.color_code || '');
                $sizeLabel.text(size ? (size.size || size.name || size.label || '') : '');
            }

            function updatePricing() {
                var color = activeColor();
                var sizes = activeSizeOptions();
                var size = sizes[currentSizeIndex] || null;
                var currency = product.base_currency || 'SYP';
                var quantity = Math.max(1, Math.min(99, parseInt($qtyInput.val(), 10) || 1));
                var currentLabel = (size && size.price_current_label) || color.price_current_label || product.price_current_label || product.price_label || '';
                var compareLabel = (size && size.compare_price_label) || color.compare_price_label || product.compare_price_label || '';
                var currentBase = (size && size.price_current) || color.price_current || product.price_current || product.base_price || 0;
                var compareBase = (size && size.compare_price) || color.compare_price || product.compare_price || 0;
                var totalBase = Number(currentBase || 0) * quantity;
                var totalLabel = totalBase > 0 && currency ? (Math.round(totalBase)).toLocaleString() + ' ' + currency : currentLabel;

                $currentPrice
                    .text(currentLabel)
                    .attr('data-base-price', currentBase || 0)
                    .attr('data-base-currency', currency);

                $submitPrice.text(totalLabel);

                $comparePrice
                    .text(compareLabel || '')
                    .attr('data-base-price', compareBase || 0)
                    .attr('data-base-currency', currency)
                    .toggleClass('d-none', !compareLabel);

                if (window.updateCurrencyConvertedPrices) {
                    window.updateCurrencyConvertedPrices();
                }
            }

            function selectedSizeInput() {
                return $('[data-detail-sizes] input[name="detail_size"]:checked').first();
            }

            function renderSizeChartModal() {
                var $modal = $('#find_size');
                var chart = product && product.size_chart ? product.size_chart : {};
                var rows = Array.isArray(chart.rows) ? chart.rows : [];
                var columns = Array.isArray(chart.columns) ? chart.columns : [];
                var $table = $modal.find('[data-size-chart-table]');
                var $head = $modal.find('[data-size-chart-head]');
                var $body = $modal.find('[data-size-chart-body]');
                var $empty = $modal.find('[data-size-chart-empty]');
                var $guideWrap = $modal.find('[data-size-chart-guide-wrap]');
                var $guideImage = $modal.find('[data-size-chart-guide-image]');
                var $tableWrap = $modal.find('[data-size-chart-table-wrap]');
                var guideImage = String(chart.guide_image || '').trim();

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

                var headHtml = '';
                var bodyHtml = '';

                columns.forEach(function (column) {
                    headHtml += '<th>' + escapeHtml(column.label || '') + '</th>';
                });

                rows.forEach(function (row) {
                    bodyHtml += '<tr>';
                    columns.forEach(function (column) {
                        var value = row[column.key] ?? '';
                        bodyHtml += '<td>' + escapeHtml(value === null || value === undefined || value === '' ? '-' : String(value)) + '</td>';
                    });
                    bodyHtml += '</tr>';
                });

                $head.html(headHtml);
                $body.html(bodyHtml);
                $empty.addClass('d-none');
                $table.removeClass('d-none');
            }

            function syncCartState(response) {
                var $fragment = $('<div>').html(response.cart_html || '');
                var $newModal = $fragment.find('#shoppingCart');
                var count = (response.cart_state && response.cart_state.count) || 0;

                $('[data-cart-count]').text(count);

                if ($newModal.length && $('#shoppingCart').length) {
                    var $modal = $('#shoppingCart');
                    var $newSubtotal = $newModal.find('[data-cart-subtotal]');
                    var $subtotal = $modal.find('[data-cart-subtotal]');

                    $modal.find('[data-cart-items]').html($newModal.find('[data-cart-items]').html());

                    if ($subtotal.length && $newSubtotal.length) {
                        $subtotal.text($newSubtotal.text());
                        $subtotal.attr('data-base-price', $newSubtotal.attr('data-base-price') || 0);
                        $subtotal.attr('data-base-currency', $newSubtotal.attr('data-base-currency') || $('.js-currency-select').val() || '');
                    }

                    if (window.updateCurrencyConvertedPrices) {
                        window.updateCurrencyConvertedPrices();
                    }
                }
            }

            function selectColor(index) {
                currentColorIndex = Number(index || 0);
                renderGallery(activeColor());
                renderSizes(activeColor());
                updateLabels();
                updatePricing();
            }

            $(document).on('change', '[data-detail-colors] input[name="detail_color"]', function () {
                selectColor($(this).data('color-index') || 0);
            });

            $(document).on('change', '[data-detail-sizes] input[name="detail_size"]', function () {
                currentSizeIndex = Number($(this).data('size-index') || 0);
                updateLabels();
                updatePricing();
            });

            $(document).on('click', '[data-detail-qty]', function () {
                var current = parseInt($qtyInput.val(), 10) || 1;
                $qtyInput.val($(this).data('detail-qty') === 'decrease' ? Math.max(1, current - 1) : Math.min(99, current + 1));
                updatePricing();
            });

            $(document).on('click', '[data-detail-find-size]', function (event) {
                event.preventDefault();
                renderSizeChartModal();
                $('#find_size').modal('show');
            });

            $form.on('submit', function (event) {
                event.preventDefault();

                var url = String($form.data('cart-url') || '');
                if (!url || !$submit.length) {
                    return;
                }

                var color = activeColor();
                var $size = selectedSizeInput();
                var quantity = Math.max(1, Math.min(99, parseInt($qtyInput.val(), 10) || 1));
                var payload = {
                    quantity: quantity,
                    color: color.name || '',
                    color_name: color.name || '',
                    color_id: color.id || '',
                    color_code: color.color_code || ''
                };

                if ($size.length) {
                    payload.size = $size.val() || '';
                    payload.size_id = $size.data('size-id') || '';
                    payload.size_code = $size.data('size-code') || '';
                    payload.variant_id = $size.data('variant-id') || '';
                }

                $submit.prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: payload,
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
                        Accept: 'application/json'
                    }
                }).done(function (response) {
                    syncCartState(response || {});
                    $('#shoppingCart').modal('show');
                }).fail(function (xhr) {
                    console.error('Detail add-to-cart failed', xhr);
                }).always(function () {
                    $submit.prop('disabled', false);
                });
            });

            renderSizeChartModal();
            selectColor(0);
        })(jQuery);
    </script>
@endpush
