<!DOCTYPE html>
@php
    $pageTitle = __('front.brand');
    $locale = app()->getLocale();
@endphp
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ __('front.brand') }}">
    <link rel="shortcut icon" href="{{ asset('images/logo/favicon.png') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('images/logo/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/font-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-select.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body class="preload-wrapper {{ $locale === 'ar' ? 'rtl' : '' }}">
    <div class="preload preload-container">
        <div class="preload-logo">
            <img src="{{ asset('images/logo/loader.png') }}" alt="{{ __('front.ui.loading') }}" class="preload-logo-img">
        </div>
    </div>

    <div id="wrapper">
        @include('frontend.partials.announcement-bar', [
            'tickerItems' => $ticker_items,
            'socialLinks' => $social_links,
        ])

        @include('frontend.partials.header', [
            'navCategories' => $nav_categories,
            'currencyOptions' => $currency_options,
            'siteName' => $site_name,
            'cartCount' => $cart_count ?? 0,
        ])

        <main>
            @include('frontend.partials.slider', [
                'slides' => $hero_slides,
            ])

            @include('frontend.partials.collections', [
                'collections' => $collections,
            ])

            @include('frontend.partials.product-section', [
                'sectionId' => 'trending-now',
                'title' => __('front.home.trending_now'),
                'products' => $trending_products,
            ])

            <section class="tf-slideshow about-us-page parallax-banner position-relative">
                <div class="banner-wrapper" aria-label="Parallax banner"></div>
            </section>

            @include('frontend.partials.product-section', [
                'sectionId' => 'new-arrivals',
                'title' => __('front.home.new_arrivals'),
                'products' => $new_products,
            ])

            @include('frontend.partials.store-section', [
                'branches' => $branches,
            ])
        </main>

        @include('frontend.partials.footer', [
            'contact' => $contact,
            'socialLinks' => $social_links,
            'footerPages' => $footer_pages,
            'collections' => $collections,
        ])

        @include('frontend.partials.toolbar-bottom', [
            'cartCount' => $cart_count ?? 0,
        ])
        @include('frontend.partials.mobile-menu', [
            'navCategories' => $nav_categories,
            'quickLinks' => $quick_links,
        ])
        @include('frontend.partials.search-canvas', [
            'quickLinks' => $quick_links,
        ])
        @include('frontend.partials.shopping-cart', [
            'cartState' => $cart_state ?? [],
        ])
        @include('frontend.partials.auth-modals')
        @include('frontend.partials.quick-add')
        @include('frontend.partials.quick-view')
        @include('frontend.partials.find-size')
    </div>

    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"></path>
        </svg>
    </div>

    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('js/carousel.js') }}"></script>
    <script src="{{ asset('js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('js/lazysize.min.js') }}"></script>
    <script src="{{ asset('js/count-down.js') }}"></script>
    <script src="{{ asset('js/wow.min.js') }}"></script>
    <script src="{{ asset('js/multiple-modal.js') }}"></script>
    <script>
        try {
            localStorage.setItem('dir', @json($locale === 'ar' ? 'rtl' : 'ltr'));
        } catch (e) {}
    </script>
    <script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
    <script>
        (function ($) {
            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    })[char];
                });
            }

            function parseProduct(value) {
                if (!value) {
                    return null;
                }

                if (typeof value === 'string') {
                    try {
                        return JSON.parse(value);
                    } catch (error) {
                        return null;
                    }
                }

                return value;
            }

            function csrfToken() {
                return $('meta[name="csrf-token"]').attr('content') || '';
            }

            function requestCart(url, method, data) {
                return $.ajax({
                    url: url,
                    type: method,
                    data: data || {},
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json'
                    }
                });
            }

            function syncCartState(response) {
                if (!response) {
                    return;
                }

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

              function buildOptionMarkup(items, selectedValue, type) {
                  var html = '';

                  $.each(items || [], function (index, item) {
                      var value = item.name || item.label || item.size || item.value || '';
                      var soldOut = type === 'size' ? isOptionSoldOut(item) : false;
                      var checked = ! soldOut && selectedValue && String(selectedValue) === String(value) ? 'checked' : (!selectedValue && index === 0 && !soldOut ? 'checked' : '');
                      var id = type + '-' + index + '-' + Math.random().toString(36).slice(2, 8);
                      var gallery = [];

                      if (type === 'color') {
                          gallery = Array.isArray(item.gallery) && item.gallery.length
                            ? item.gallery
                            : (item.image ? [item.image] : []);
                    }

                      if (type === 'size') {
                          html += '<input type="radio" name="size" id="' + id + '" ' + checked + (soldOut ? ' disabled' : '') + ' value="' + escapeHtml(value) + '"' + (item.size_id ? ' data-size-id="' + escapeHtml(item.size_id) + '"' : '') + (item.size_code ? ' data-size-code="' + escapeHtml(item.size_code) + '"' : '') + (item.variant_id ? ' data-variant-id="' + escapeHtml(item.variant_id) + '"' : '') + (item.product_color_id ? ' data-product-color-id="' + escapeHtml(item.product_color_id) + '"' : '') + '>';
                          html += '<label class="style-text" for="' + id + '" data-value="' + escapeHtml(value) + '"' + (soldOut ? ' aria-disabled="true"' : '') + '>';
                          html += '<span class="size-label">' + escapeHtml(value) + '</span>';
                          html += '</label>';
                      } else {
                          html += '<input type="radio" name="color" id="' + id + '" ' + checked + ' value="' + escapeHtml(value) + '" data-gallery="' + escapeHtml(JSON.stringify(gallery)) + '"' + (item.id ? ' data-color-id="' + escapeHtml(item.id) + '"' : '') + (item.color_code ? ' data-color-code="' + escapeHtml(item.color_code) + '"' : '') + (item.name ? ' data-color-name="' + escapeHtml(item.name) + '"' : '') + '>';
                          html += '<label class="hover-tooltip radius-60" for="' + id + '" data-value="' + escapeHtml(value) + '">';
                          html += '<span class="btn-checkbox ' + escapeHtml(item.class_name || 'four-Black') + '"></span>';
                          html += '<span class="tooltip">' + escapeHtml(value) + '</span>';
                        html += '</label>';
                    }
                });

                  return html;
              }

              function isOptionSoldOut(item) {
                  if (! item) {
                      return false;
                  }

                  if (item.is_sold_out === true || item.available === false) {
                      return true;
                  }

                  if (typeof item.quantity !== 'undefined') {
                      return Number(item.quantity) <= 0;
                  }

                  return false;
                }

                function pickAvailableSize(items, preferred) {
                    var normalized = normalizeOptionList(items);
                    var selected = '';
                  var fallback = '';

                    $.each(normalized, function (index, item) {
                        var value = item.name || item.label || item.size || item.value || '';
                        var soldOut = isOptionSoldOut(item);

                      if (! fallback && value) {
                          fallback = value;
                      }

                      if (! soldOut && value && preferred && String(value).toLowerCase() === String(preferred).toLowerCase()) {
                          selected = value;
                          return false;
                      }

                      if (! soldOut && ! selected && value) {
                          selected = value;
                      }
                  });

                  return selected || fallback;
              }

              function hasAvailableSize(items) {
                  var normalized = normalizeOptionList(items);
                  var available = false;

                  $.each(normalized, function (index, item) {
                      if (! isOptionSoldOut(item)) {
                          available = true;
                          return false;
                      }
                  });

                  return available;
              }

            function parseJsonAttribute($element, attribute, fallback) {
                var raw = $element.attr(attribute) || '';

                if (!raw) {
                    return fallback;
                }

                try {
                    return JSON.parse(raw);
                } catch (error) {
                    return fallback;
                }
            }

            function formatQuickPrice(value, currency) {
                var amount = Number(value);

                if (!Number.isFinite(amount) || amount <= 0) {
                    return '';
                }

                return currency
                    ? amount.toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' ' + currency
                    : amount.toLocaleString('en-US', { maximumFractionDigits: 0 });
            }

            function findProductColor(product, colorRef) {
                var colors = Array.isArray(product.colors) ? product.colors : [];
                var fallback = colors.length ? colors[0] : {};

                if (!colorRef) {
                    return fallback;
                }

                if (typeof colorRef === 'object') {
                    if (colorRef.id) {
                        for (var byId = 0; byId < colors.length; byId++) {
                            if (String(colors[byId].id || '') === String(colorRef.id)) {
                                return colors[byId];
                            }
                        }
                    }

                    if (colorRef.code) {
                        for (var byCode = 0; byCode < colors.length; byCode++) {
                            if (String(colors[byCode].color_code || '').toLowerCase() === String(colorRef.code).toLowerCase()) {
                                return colors[byCode];
                            }
                        }
                    }

                    if (colorRef.name) {
                        for (var byName = 0; byName < colors.length; byName++) {
                            if (String(colors[byName].name || '').toLowerCase() === String(colorRef.name).toLowerCase()) {
                                return colors[byName];
                            }
                        }
                    }

                    if (colorRef.className) {
                        for (var byClass = 0; byClass < colors.length; byClass++) {
                            if (String(colors[byClass].class_name || '').toLowerCase() === String(colorRef.className).toLowerCase()) {
                                return colors[byClass];
                            }
                        }
                    }
                }

                var colorName = String(colorRef || '');

                for (var index = 0; index < colors.length; index++) {
                    var color = colors[index] || {};
                    if (String(color.name || '').toLowerCase() === String(colorName).toLowerCase()) {
                        return color;
                    }
                }

                return fallback;
            }

            function getCardSelectedColor($card) {
                var $active = $card.find('.list-color-item.color-swatch.active').first();

                if (!$active.length) {
                    $active = $card.find('.list-color-item.color-swatch').first();
                }

                return {
                    id: $active.data('colorId') || '',
                    name: $active.data('colorName') || '',
                    code: $active.data('colorCode') || '',
                    className: $active.data('colorClass') || '',
                    image: $active.data('colorImage') || ''
                };
            }

            function syncCardSelectedColor($card, $swatch) {
                if (!$card.length || !$swatch.length) {
                    return;
                }

                var selected = {
                    id: $swatch.data('colorId') || '',
                    name: $swatch.data('colorName') || '',
                    code: $swatch.data('colorCode') || '',
                    className: $swatch.data('colorClass') || '',
                    image: $swatch.data('colorImage') || ''
                };

                $card
                    .attr('data-selected-color-id', selected.id)
                    .attr('data-selected-color-name', selected.name)
                    .attr('data-selected-color-code', selected.code)
                    .attr('data-selected-color-class', selected.className)
                    .attr('data-selected-color-image', selected.image);

                if (selected.name) {
                    $card.data('selectedColor', selected);
                }
            }

            function settleProductCardSkeleton($card) {
                if (!$card || !$card.length) {
                    return;
                }

                $card.find('img.img-product').each(function () {
                    var $img = $(this);
                    if (this.complete && this.naturalWidth > 0) {
                        return;
                    }

                    $img.one('load error', function () {
                        $card.addClass('is-loaded');
                    });
                });

                if ($card.find('img.img-product').toArray().every(function (img) { return img.complete && img.naturalWidth > 0; })) {
                    $card.addClass('is-loaded');
                }
            }

            function findSizePricing(product, sizeName) {
                var pricing = product && product.size_pricing ? product.size_pricing : {};
                var keys = Object.keys(pricing);
                var fallback = keys.length ? pricing[keys[0]] : null;

                if (!sizeName) {
                    return fallback;
                }

                for (var index = 0; index < keys.length; index++) {
                    var key = keys[index];
                    if (String(key || '').toLowerCase() === String(sizeName).toLowerCase()) {
                        return pricing[key];
                    }
                }

                return fallback;
            }

            function normalizeOptionList(items) {
                return (items || []).map(function (item) {
                    return typeof item === 'string' ? { name: item } : item;
                });
            }

              function renderQuickSizes($modal, sizes, selectedSize, prefix) {
                  var $sizes = $modal.find(prefix === 'qadd' ? '[data-qadd-sizes]' : '[data-qv-sizes]');
                  var items = normalizeOptionList(sizes);

                  if (!$sizes.length) {
                      return;
                  }

                  selectedSize = pickAvailableSize(items, selectedSize);
                  $sizes.html(buildOptionMarkup(items, selectedSize, 'size'));
              }

            function syncQuickViewPricing($modal, product, selectedSize) {
                var currentSelector = '[data-qv-price-current]';
                var compareSelector = '[data-qv-price-old]';
                var submitSelector = '[data-qv-submit-price]';
                var qty = parseInt($modal.find('input[name="number"]').val(), 10) || 1;
                qty = qty > 0 ? qty : 1;
                var currency = product.base_currency || product.currency || $('.js-currency-select').val() || '';
                var pricing = findSizePricing(product, selectedSize);
                var unitCurrent = pricing && Number(pricing.price_current) > 0
                    ? Number(pricing.price_current)
                    : Number(product.price_current || product.base_price || 0);
                var unitCompare = pricing && Number(pricing.compare_price) > 0
                    ? Number(pricing.compare_price)
                    : Number(product.compare_price || 0);
                var currentValue = unitCurrent * qty;
                var compareValue = unitCompare > unitCurrent ? unitCompare * qty : 0;
                var currentLabel = formatQuickPrice(currentValue, currency) || product.price_label || '';
                var compareLabel = Number(compareValue) > Number(currentValue) ? formatQuickPrice(compareValue, currency) : '';

                $modal.find(currentSelector)
                    .attr('data-base-price', currentValue)
                    .attr('data-base-currency', currency)
                    .text(currentLabel);
                $modal.find(submitSelector)
                    .attr('data-base-price', currentValue)
                    .attr('data-base-currency', currency)
                    .text(currentLabel);

                if ($modal.find(compareSelector).length) {
                    $modal.find(compareSelector)
                        .attr('data-base-price', compareValue)
                        .attr('data-base-currency', currency)
                        .text(compareLabel)
                        .toggleClass('d-none', !compareLabel);
                }

                var $qaddPrice = $modal.find('[data-qadd-price]');
                if ($qaddPrice.length) {
                    $qaddPrice
                        .attr('data-base-price', currentValue)
                        .attr('data-base-currency', currency)
                        .text(currentLabel);
                }

                if (window.updateCurrencyConvertedPrices) {
                    window.updateCurrencyConvertedPrices();
                }
            }

            function syncQuickViewSelection($modal, options) {
                options = options || {};
                var product = parseProduct($modal.data('product')) || {};
                var selectedColorName = readSelected($modal, 'color');
                  var colorData = findProductColor(product, selectedColorName);
                  var sizes = Array.isArray(colorData.size_options) && colorData.size_options.length
                      ? colorData.size_options
                      : (Array.isArray(product.size_options) && product.size_options.length ? product.size_options : (Array.isArray(colorData.sizes) && colorData.sizes.length ? colorData.sizes : (Array.isArray(product.sizes) ? product.sizes : [])));
                  var selectedSize = readSelected($modal, 'size');
                  var available = hasAvailableSize(sizes);

                  $modal.find('[data-qv-color-label]').text(colorData.name || selectedColorName || '');
                  $modal.find('[data-qv-color-code]').text(colorData.color_code || '—');
                  renderQuickGallery($modal, colorData.gallery || []);
                  renderQuickSizes($modal, sizes, selectedSize, 'qv');

                  selectedSize = readSelected($modal, 'size');
                  $modal.find('[data-qv-size-label]').text(selectedSize || product.default_size || (sizes[0] && (sizes[0].name || sizes[0].label || sizes[0].size || sizes[0].value || sizes[0])) || '');

                  if (options.updatePrice !== false) {
                      syncQuickViewPricing($modal, product, selectedSize);
                  }

                  $modal.find('[data-cart-submit]')
                      .prop('disabled', ! available)
                      .toggleClass('disabled', ! available)
                      .attr('aria-disabled', ! available ? 'true' : 'false');
              }

            function renderSizeChart($modal, product) {
                var chart = product.size_chart || {};
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

                if (!columns.length || !rows.length) {
                    $table.addClass('d-none');
                    $empty.removeClass('d-none');
                    $head.empty();
                    $body.empty();
                    return;
                }

                var headHtml = '';
                $.each(columns, function (index, column) {
                    headHtml += '<th>' + escapeHtml(column.label || '') + '</th>';
                });

                var bodyHtml = '';
                $.each(rows, function (rowIndex, row) {
                    bodyHtml += '<tr>';
                    $.each(columns, function (columnIndex, column) {
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

            function renderQuickModal($modal, product, prefix, options) {
                options = options || {};
                var titleSelector = prefix === 'qadd' ? '[data-qadd-title-link]' : '[data-qv-title]';
                var priceSelector = prefix === 'qadd' ? '[data-qadd-price]' : '[data-qv-price-current]';
                var submitPriceSelector = prefix === 'qadd' ? '[data-qadd-submit-price]' : '[data-qv-submit-price]';
                var imageSelector = prefix === 'qadd' ? '[data-qadd-image]' : null;
                var descriptionSelector = prefix === 'qadd' ? null : '[data-qv-description]';
                var detailSelector = prefix === 'qadd' ? '[data-qadd-title-link]' : '[data-qv-title], [data-qv-detail]';
                var badgeSelector = prefix === 'qadd' ? null : '[data-qv-badge]';
                var productCodeSelector = prefix === 'qadd' ? null : '[data-qv-product-code]';
                var colorCodeSelector = prefix === 'qadd' ? null : '[data-qv-color-code]';
                var colorLabelSelector = prefix === 'qadd' ? '[data-qadd-color-label]' : '[data-qv-color-label]';
                var sizeLabelSelector = prefix === 'qadd' ? '[data-qadd-size-label]' : '[data-qv-size-label]';
                var colorsSelector = prefix === 'qadd' ? '[data-qadd-colors]' : '[data-qv-colors]';
                var sizesSelector = prefix === 'qadd' ? '[data-qadd-sizes]' : '[data-qv-sizes]';
                var qtySelector = prefix === 'qadd' ? '[data-qadd-qty]' : '[data-qv-qty]';
                var submitSelector = '[data-cart-submit]';
                var gallerySelector = '[data-qv-gallery]';
                var sizeChartSelector = '[data-qv-find-size]';
                var initialColor = options.selectedColor || product.default_color || (product.colors && product.colors[0] ? product.colors[0].name : '');
                var selectedColorData = findProductColor(product, initialColor);
                var defaultColor = selectedColorData.name || selectedColorData.code || selectedColorData.className || '';
                var initialSizes = Array.isArray(selectedColorData.size_options) && selectedColorData.size_options.length
                    ? selectedColorData.size_options
                    : (Array.isArray(product.size_options) && product.size_options.length ? product.size_options : (Array.isArray(selectedColorData.sizes) && selectedColorData.sizes.length ? selectedColorData.sizes : (Array.isArray(product.sizes) ? product.sizes : [])));
                var defaultSize = options.selectedSize || selectedColorData.default_size || product.default_size || pickAvailableSize(initialSizes, product.default_size || (initialSizes && initialSizes[0] ? (initialSizes[0].name || initialSizes[0].label || initialSizes[0].size || initialSizes[0].value || initialSizes[0]) : ''));
                var detailUrl = product.detail_url || product.url || '#';
                var priceLabel = product.price_current_label || product.price_label || '';
                var compareLabel = product.compare_price_label || '';

                $modal.data('product', product);
                $modal.find(titleSelector).text(product.title || '');
                $modal.find(detailSelector).attr('href', detailUrl);
                $modal.find(priceSelector).text(priceLabel);
                $modal.find(submitPriceSelector).text(priceLabel);
                $modal.find(submitSelector).attr('data-cart-url', product.cart_add_url || '');
                $modal.find(qtySelector).val(1);

                if (badgeSelector) {
                    var badgeLabel = product.badge || '';
                    var $badge = $modal.find(badgeSelector);
                    if (badgeLabel) {
                        $badge
                            .removeClass('d-none')
                            .text(badgeLabel)
                            .attr('data-badge-class', product.badge_class || '');
                    } else {
                        $badge.addClass('d-none').text('').removeAttr('data-badge-class');
                    }
                }

                if (productCodeSelector) {
                    $modal.find(productCodeSelector).text(product.product_code || '—');
                }

                if (colorCodeSelector) {
                    $modal.find(colorCodeSelector).text(product.default_color_code || '—');
                }

                if (imageSelector) {
                    $modal.find(imageSelector).attr('src', product.image || '');
                }

                if (descriptionSelector) {
                    var description = product.description || '';
                    $modal.find(descriptionSelector).html(description ? '<p>' + escapeHtml(description) + '</p>' : '');
                }

                if (sizeChartSelector) {
                    $modal.find(sizeChartSelector).toggleClass('d-none', ! product.has_size_chart);
                }

                if (colorsSelector) {
                    $modal.find(colorsSelector).html(buildOptionMarkup(product.colors || [], defaultColor, 'color'));
                }

                if (sizesSelector) {
                    var sizeItems = initialSizes.length ? initialSizes : normalizeOptionList(product.sizes || []);
                    $modal.find(sizesSelector).html(buildOptionMarkup(sizeItems, defaultSize, 'size'));
                    $modal.find(submitSelector)
                        .prop('disabled', ! hasAvailableSize(sizeItems))
                        .toggleClass('disabled', ! hasAvailableSize(sizeItems))
                        .attr('aria-disabled', ! hasAvailableSize(sizeItems) ? 'true' : 'false');
                }

                if (prefix === 'qv') {
                    syncQuickViewSelection($modal, { updatePrice: false });
                    var selectedQvSize = readSelected($modal, 'size');
                    syncQuickViewPricing($modal, product, selectedQvSize);
                } else if (prefix === 'qadd') {
                    syncProductModalSelection($modal, 'qadd', { updatePrice: false });
                    var selectedQaddSize = readSelected($modal, 'size');
                    formatQuickAddPricing($modal, product, selectedQaddSize);
                }
            }

            function renderQuickGallery($modal, gallery) {
                var $gallery = $modal.find('[data-qv-gallery]');
                var html = '';
                var items = Array.isArray(gallery) && gallery.length ? gallery : [];

                if (!items.length) {
                    items = [$modal.data('product') && $modal.data('product').image ? $modal.data('product').image : ''];
                }

                $.each(items, function (index, src) {
                    if (!src) {
                        return;
                    }

                    html += '<div class="swiper-slide"><div class="item"><img src="' + escapeHtml(src) + '" alt=""></div></div>';
                });

                if (!html) {
                    html = '<div class="swiper-slide"><div class="item"><img src="" alt=""></div></div>';
                }

                $gallery.html(html);

                var swiperEl = $modal.find('.tf-single-slide')[0];
                  if (swiperEl && swiperEl.swiper && typeof swiperEl.swiper.update === 'function') {
                      swiperEl.swiper.update();
                      swiperEl.swiper.slideTo(0, 0, false);
                  }
              }

            function getModalSelectors(prefix) {
                return {
                    colorLabel: prefix === 'qadd' ? '[data-qadd-color-label]' : '[data-qv-color-label]',
                    colorCode: prefix === 'qadd' ? null : '[data-qv-color-code]',
                    gallery: prefix === 'qadd' ? null : '[data-qv-gallery]',
                    image: prefix === 'qadd' ? '[data-qadd-image]' : null,
                    sizeLabel: prefix === 'qadd' ? '[data-qadd-size-label]' : '[data-qv-size-label]',
                    sizes: prefix === 'qadd' ? '[data-qadd-sizes]' : '[data-qv-sizes]',
                    submit: '[data-cart-submit]',
                    priceCurrent: prefix === 'qadd' ? '[data-qadd-price]' : '[data-qv-price-current]',
                    priceOld: prefix === 'qadd' ? '[data-qadd-price-old]' : '[data-qv-price-old]',
                    submitPrice: prefix === 'qadd' ? '[data-qadd-submit-price]' : '[data-qv-submit-price]'
                };
            }

            function formatQuickAddPricing($modal, product, selectedSize) {
                var qty = parseInt($modal.find('input[name="number"]').val(), 10) || 1;
                qty = qty > 0 ? qty : 1;
                var currency = product.base_currency || product.currency || $('.js-currency-select').val() || '';
                var pricing = findSizePricing(product, selectedSize);
                var unitCurrent = pricing && Number(pricing.price_current) > 0
                    ? Number(pricing.price_current)
                    : Number(product.price_current || product.base_price || 0);
                var unitCompare = pricing && Number(pricing.compare_price) > 0
                    ? Number(pricing.compare_price)
                    : Number(product.compare_price || 0);
                var totalCurrent = unitCurrent * qty;
                var totalCompare = unitCompare > unitCurrent ? unitCompare * qty : 0;
                var currentLabel = formatQuickPrice(totalCurrent, currency) || product.price_label || '';
                var compareLabel = totalCompare > totalCurrent ? formatQuickPrice(totalCompare, currency) : '';
                var selectors = getModalSelectors('qadd');

                if (selectors.priceCurrent) {
                    $modal.find(selectors.priceCurrent)
                        .attr('data-base-price', totalCurrent)
                        .attr('data-base-currency', currency)
                        .text(currentLabel);
                }

                if (selectors.submitPrice) {
                    $modal.find(selectors.submitPrice)
                        .attr('data-base-price', totalCurrent)
                        .attr('data-base-currency', currency)
                        .text(currentLabel);
                }

                if (selectors.priceOld) {
                    $modal.find(selectors.priceOld)
                        .attr('data-base-price', totalCompare)
                        .attr('data-base-currency', currency)
                        .text(compareLabel)
                        .toggleClass('d-none', ! compareLabel);
                }

                if (window.updateCurrencyConvertedPrices) {
                    window.updateCurrencyConvertedPrices();
                }
            }

            function syncProductModalSelection($modal, prefix, options) {
                options = options || {};
                var product = parseProduct($modal.data('product')) || {};
                var selectors = getModalSelectors(prefix);
                var selectedColorName = readSelected($modal, 'color');
                var colorData = findProductColor(product, selectedColorName);
                var sizes = Array.isArray(colorData.size_options) && colorData.size_options.length
                    ? colorData.size_options
                    : (Array.isArray(product.size_options) && product.size_options.length
                        ? product.size_options
                        : (Array.isArray(colorData.sizes) && colorData.sizes.length
                            ? colorData.sizes
                            : (Array.isArray(product.sizes) ? product.sizes : [])));
                var selectedSize = readSelected($modal, 'size');
                var available = hasAvailableSize(sizes);

                if (selectors.colorLabel) {
                    $modal.find(selectors.colorLabel).text(colorData.name || selectedColorName || '');
                }

                if (selectors.colorCode) {
                    $modal.find(selectors.colorCode).text(colorData.color_code || '—');
                }

                if (selectors.gallery) {
                    renderQuickGallery($modal, colorData.gallery || []);
                }

                if (selectors.image) {
                    var imageSrc = colorData.image || (Array.isArray(colorData.gallery) && colorData.gallery[0]) || product.image || '';
                    $modal.find(selectors.image).attr('src', imageSrc);
                }

                if (selectors.sizes) {
                    renderQuickSizes($modal, sizes, selectedSize, 'qadd');
                }

                selectedSize = readSelected($modal, 'size');

                if (selectors.sizeLabel) {
                    $modal.find(selectors.sizeLabel).text(selectedSize || product.default_size || (sizes[0] && (sizes[0].name || sizes[0].label || sizes[0].size || sizes[0].value || sizes[0])) || '');
                }

                if (options.updatePrice !== false) {
                    syncQuickViewPricing($modal, product, selectedSize);
                }

                if (selectors.submit) {
                    $modal.find(selectors.submit)
                        .prop('disabled', ! available)
                        .toggleClass('disabled', ! available)
                        .attr('aria-disabled', ! available ? 'true' : 'false');
                }

                if (prefix === 'qadd' && options.updatePrice !== false) {
                      formatQuickAddPricing($modal, product, selectedSize);
                  }
            }

            function syncQuickViewColor($modal) {
                syncQuickViewSelection($modal);
            }

            function readSelected($modal, name) {
                var $input = $modal.find('input[name="' + name + '"]:checked');
                if ($input.length) {
                    return $input.val();
                }

                return '';
            }

            function submitQuickModal($button) {
                var $modal = $button.closest('.modal');
                var product = parseProduct($modal.data('product'));
                var cartUrl = $button.attr('data-cart-url') || (product && product.cart_add_url) || '';
                var prefix = $modal.attr('id') === 'quick_add' ? 'qadd' : 'qv';
                var $selectedColor = $modal.find('input[name="color"]:checked');
                var $selectedSize = $modal.find('input[name="size"]:checked');
                var quantity = parseInt($modal.find('input[name="number"]').val(), 10) || 1;

                if (!cartUrl) {
                    return;
                }

                requestCart(cartUrl, 'POST', {
                    quantity: quantity > 0 ? quantity : 1,
                    size: readSelected($modal, 'size'),
                    size_id: $selectedSize.data('sizeId') || '',
                    size_code: $selectedSize.data('sizeCode') || '',
                    variant_id: $selectedSize.data('variantId') || '',
                    color: readSelected($modal, 'color'),
                    color_name: readSelected($modal, 'color'),
                    color_id: $selectedColor.data('colorId') || '',
                    color_code: $selectedColor.data('colorCode') || ''
                }).done(function (response) {
                    syncCartState(response);
                    $modal.modal('hide');
                    $('#shoppingCart').modal('show');
                });
            }

            $(document).on('click', '.card-product .quick-add, .card-product .quickview', function (event) {
                event.preventDefault();

                var $card = $(this).closest('.card-product');
                var product = parseProduct($card.data('product'));
                if (!product) {
                    return;
                }

                var selectedColor = getCardSelectedColor($card);
                var selectedColorRef = selectedColor.name || selectedColor.code || selectedColor.className || selectedColor.id || '';
                if (selectedColorRef) {
                    product.default_color = selectedColorRef;
                }

                if ($(this).hasClass('quickview')) {
                    renderQuickModal($('#quick_view'), product, 'qv', { selectedColor: selectedColor });
                    $('#quick_view').modal('show');
                    return;
                }

                renderQuickModal($('#quick_add'), product, 'qadd', { selectedColor: selectedColor });
                $('#quick_add').modal('show');
            });

            $(document).on('click', '.card-product .list-color-item.color-swatch', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var $swatch = $(this);
                var $card = $swatch.closest('.card-product');
                var imageSrc = $swatch.data('colorImage') || $swatch.find('img').attr('src') || '';

                $card.find('.list-color-item.color-swatch').removeClass('active');
                $swatch.addClass('active');
                syncCardSelectedColor($card, $swatch);

                if (imageSrc) {
                    $card.find('.img-product').attr('src', imageSrc).attr('data-src', imageSrc);
                }
            });

            $(function () {
                $('.card-product').each(function () {
                    settleProductCardSkeleton($(this));
                });
            });

            $(document).on('click', '#quick_view [data-cart-submit], #quick_add [data-cart-submit]', function (event) {
                event.preventDefault();
                submitQuickModal($(this));
            });

              $(document).on('change', '#quick_view input[name="color"]', function () {
                  syncQuickViewColor($(this).closest('#quick_view'));
              });

              $(document).on('change', '#quick_view input[name="size"]', function () {
                  syncQuickViewSelection($(this).closest('#quick_view'));
              });

              $(document).on('change input', '#quick_view input[name="number"]', function () {
                  var $modal = $(this).closest('#quick_view');
                  var product = parseProduct($modal.data('product')) || {};
                  var selectedSize = readSelected($modal, 'size');
                  syncQuickViewPricing($modal, product, selectedSize);
              });

              $(document).on('click', '#quick_view .btn-quantity', function () {
                  var $modal = $(this).closest('#quick_view');
                  setTimeout(function () {
                      var product = parseProduct($modal.data('product')) || {};
                      var selectedSize = readSelected($modal, 'size');
                      syncQuickViewPricing($modal, product, selectedSize);
                  }, 0);
              });

              $(document).on('change', '#quick_add input[name="color"]', function () {
                  syncProductModalSelection($(this).closest('#quick_add'), 'qadd');
              });

              $(document).on('change', '#quick_add input[name="size"]', function () {
                  syncProductModalSelection($(this).closest('#quick_add'), 'qadd');
              });

              $(document).on('change input', '#quick_add input[name="number"]', function () {
                  var $modal = $(this).closest('#quick_add');
                  var product = parseProduct($modal.data('product')) || {};
                  var selectedSize = readSelected($modal, 'size');
                  formatQuickAddPricing($modal, product, selectedSize);
              });

              $(document).on('click', '#quick_add .btn-quantity', function () {
                  var $modal = $(this).closest('#quick_add');
                  setTimeout(function () {
                      var product = parseProduct($modal.data('product')) || {};
                      var selectedSize = readSelected($modal, 'size');
                      formatQuickAddPricing($modal, product, selectedSize);
                  }, 0);
              });

            $(document).on('click', '#quick_view [data-qv-find-size]', function (event) {
                event.preventDefault();

                var $quickView = $('#quick_view');
                var product = parseProduct($quickView.data('product')) || {};
                renderSizeChart($('#find_size'), product);
                $('#find_size').modal('show');
            });
        })(jQuery);
    </script>
</body>
</html>
