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

        function appendQueryParam(url, key, value) {
            if (!url || value === null || value === undefined || value === '') {
                return url;
            }

            try {
                var parsed = new URL(url, window.location.origin);
                parsed.searchParams.set(key, value);
                return parsed.toString();
            } catch (error) {
                return url + (url.indexOf('?') === -1 ? '?' : '&') + encodeURIComponent(key) + '=' + encodeURIComponent(value);
            }
        }

        function detailUrlWithColor(product, colorRef) {
            var url = product && (product.detail_url || product.url) ? (product.detail_url || product.url) : '#';
            colorRef = colorRef || {};

            url = appendQueryParam(url, 'color_id', colorRef.id || '');
            url = appendQueryParam(url, 'color_code', colorRef.code || colorRef.color_code || '');
            url = appendQueryParam(url, 'color', colorRef.name || '');

            return url;
        }

        function updateCardDetailLinks($card) {
            var product = parseProduct($card.data('product')) || {};
            var selectedColor = getCardSelectedColor($card);
            var url = detailUrlWithColor(product, selectedColor);

            $card.find('a.collection-image, .card-product-info a.title.link').attr('href', url);
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

        function normalizeOptionList(items) {
            return (items || []).map(function (item) {
                return typeof item === 'string' ? { name: item } : item;
            });
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

                if (!fallback && value) {
                    fallback = value;
                }

                if (!soldOut && value && preferred && String(value).toLowerCase() === String(preferred).toLowerCase()) {
                    selected = value;
                    return false;
                }

                if (!soldOut && !selected && value) {
                    selected = value;
                }
            });

            return selected || fallback;
        }

        function hasAvailableSize(items) {
            var available = false;

            $.each(normalizeOptionList(items), function (index, item) {
                if (!isOptionSoldOut(item)) {
                    available = true;
                    return false;
                }
            });

            return available;
        }

        function buildSwatchStyle(item) {
            item = item || {};

            var swatchStyle = item.swatch_style || item.card_swatch_style || item.computed_swatch_style || '';
            var swatchImage = item.swatch_image || item.card_swatch_image || '';
            var colorHex = item.hex || item.color_hex || '';

            if (!swatchStyle && swatchImage) {
                swatchStyle = "background-image: url('" + String(swatchImage).replace(/'/g, "\\'") + "'); background-size: cover; background-position: center; background-color: transparent;";
            }

            if (!swatchStyle && colorHex) {
                swatchStyle = 'background-color: ' + colorHex + ';';
            }

            return swatchStyle;
        }

        function isSelectedOption(item, selectedValue) {
            if (!selectedValue) {
                return false;
            }

            var selected = String(selectedValue).toLowerCase();
            var candidates = [
                item.name,
                item.label,
                item.size,
                item.value,
                item.id,
                item.color_code,
                item.class_name
            ];

            for (var index = 0; index < candidates.length; index++) {
                if (candidates[index] !== null && candidates[index] !== undefined && String(candidates[index]).toLowerCase() === selected) {
                    return true;
                }
            }

            return false;
        }

        function buildOptionMarkup(items, selectedValue, type) {
            var html = '';

            $.each(normalizeOptionList(items), function (index, item) {
                item = item || {};

                var value = item.name || item.label || item.size || item.value || '';
                var soldOut = type === 'size' ? isOptionSoldOut(item) : false;
                var checked = ! soldOut && isSelectedOption(item, selectedValue) ? 'checked' : (!selectedValue && index === 0 && !soldOut ? 'checked' : '');
                var id = type + '-' + index + '-' + Math.random().toString(36).slice(2, 8);

                if (type === 'size') {
                    html += '<input type="radio" name="size" id="' + id + '" ' + checked + (soldOut ? ' disabled' : '') + ' value="' + escapeHtml(value) + '"' + (item.size_id ? ' data-size-id="' + escapeHtml(item.size_id) + '"' : '') + (item.size_code ? ' data-size-code="' + escapeHtml(item.size_code) + '"' : '') + (item.variant_id ? ' data-variant-id="' + escapeHtml(item.variant_id) + '"' : '') + (item.product_color_id ? ' data-product-color-id="' + escapeHtml(item.product_color_id) + '"' : '') + '>';
                    html += '<label class="style-text" for="' + id + '" data-value="' + escapeHtml(value) + '"' + (soldOut ? ' aria-disabled="true"' : '') + '>';
                    html += '<span class="size-label">' + escapeHtml(value) + '</span>';
                    html += '</label>';
                } else {
                    var gallery = Array.isArray(item.gallery) && item.gallery.length ? item.gallery : (item.image ? [item.image] : []);
                    var swatchStyle = buildSwatchStyle(item);
                    var swatchImage = item.swatch_image || item.card_swatch_image || '';
                    var colorHex = item.hex || item.color_hex || '';
                    var className = item.class_name || 'four-Black';

                    html += '<input type="radio" name="color" id="' + id + '" ' + checked + ' value="' + escapeHtml(value) + '" data-gallery="' + escapeHtml(JSON.stringify(gallery)) + '"' + (item.id ? ' data-color-id="' + escapeHtml(item.id) + '"' : '') + (item.color_code ? ' data-color-code="' + escapeHtml(item.color_code) + '"' : '') + (item.name ? ' data-color-name="' + escapeHtml(item.name) + '"' : '') + ' data-color-class="' + escapeHtml(className) + '" data-color-image="' + escapeHtml(item.image || '') + '" data-color-hex="' + escapeHtml(colorHex) + '" data-color-swatch-image="' + escapeHtml(swatchImage) + '" data-color-swatch-style="' + escapeHtml(swatchStyle) + '">';
                    html += '<label class="hover-tooltip radius-60" for="' + id + '" data-value="' + escapeHtml(value) + '">';
                    html += '<span class="btn-checkbox swatch-value ' + escapeHtml(className) + '"' + (swatchStyle ? ' style="' + escapeHtml(swatchStyle) + '"' : '') + '></span>';
                    html += '<span class="tooltip">' + escapeHtml(value) + '</span>';
                    html += '</label>';
                }
            });

            return html;
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

        function cardSwatchDisplayStyle($swatch) {
            if (! $swatch || ! $swatch.length) {
                return '';
            }

            var $value = $swatch.find('.swatch-value').first();

            if (! $value.length) {
                $value = $swatch.find('.btn-checkbox').first();
            }

            if (! $value.length) {
                return '';
            }

            var inlineStyle = ($value.attr('style') || '').trim();

            if (inlineStyle) {
                return inlineStyle;
            }

            var styleParts = [];
            var bgImage = $value.css('background-image');
            var bgColor = $value.css('background-color');
            var bgSize = $value.css('background-size');
            var bgPosition = $value.css('background-position');

            if (bgImage && bgImage !== 'none') {
                styleParts.push('background-image: ' + bgImage);
                styleParts.push('background-size: ' + (bgSize && bgSize !== 'auto' ? bgSize : 'cover'));
                styleParts.push('background-position: ' + (bgPosition || 'center'));
                styleParts.push('background-color: transparent');
            } else if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
                styleParts.push('background-color: ' + bgColor);
            }

            return styleParts.length ? styleParts.join('; ') + ';' : '';
        }

        function cardSwatchClass($swatch) {
            var $value = $swatch.find('.swatch-value').first();

            if (! $value.length) {
                $value = $swatch.find('.btn-checkbox').first();
            }

            var classes = String($value.attr('class') || '').split(/\s+/).filter(function (className) {
                return className && className !== 'swatch-value' && className !== 'btn-checkbox' && className !== 'lazyload';
            });

            return classes.length ? classes.join(' ') : ($swatch.data('colorClass') || '');
        }

        function findProductColorIndex(colors, colorRef, fallbackIndex) {
            colors = Array.isArray(colors) ? colors : [];
            colorRef = colorRef || {};

            var matchValue = function (left, right) {
                return left !== null && left !== undefined && right !== null && right !== undefined && String(left).toLowerCase() === String(right).toLowerCase();
            };

            for (var index = 0; index < colors.length; index++) {
                var color = colors[index] || {};

                if (colorRef.id && matchValue(color.id, colorRef.id)) {
                    return index;
                }

                if (colorRef.code && matchValue(color.color_code, colorRef.code)) {
                    return index;
                }

                if (colorRef.name && matchValue(color.name, colorRef.name)) {
                    return index;
                }

                if (colorRef.className && matchValue(color.class_name, colorRef.className)) {
                    return index;
                }

                if (colorRef.image && matchValue(color.image, colorRef.image)) {
                    return index;
                }
            }

            fallbackIndex = Number(fallbackIndex);

            return Number.isFinite(fallbackIndex) && fallbackIndex >= 0 && fallbackIndex < colors.length ? fallbackIndex : -1;
        }

        function hydrateProductColorsFromCard(product, $card) {
            product = $.extend(true, {}, product || {});
            product.colors = Array.isArray(product.colors) ? product.colors : [];

            if (! $card || ! $card.length || ! product.colors.length) {
                return product;
            }

            $card.find('.list-color-item.color-swatch').each(function (index) {
                var $swatch = $(this);
                var colorRef = {
                    id: $swatch.data('colorId') || '',
                    name: $swatch.data('colorName') || '',
                    code: $swatch.data('colorCode') || '',
                    className: $swatch.data('colorClass') || '',
                    image: $swatch.data('colorImage') || ''
                };
                var colorIndex = findProductColorIndex(product.colors, colorRef, index);

                if (colorIndex < 0) {
                    return;
                }

                var swatchStyle = cardSwatchDisplayStyle($swatch);
                var className = cardSwatchClass($swatch);
                var swatchImage = $swatch.data('colorSwatch') || $swatch.data('colorSwatchImage') || '';
                var hex = $swatch.data('colorHex') || '';

                product.colors[colorIndex].class_name = className || product.colors[colorIndex].class_name;
                product.colors[colorIndex].swatch_style = swatchStyle || product.colors[colorIndex].swatch_style || '';
                product.colors[colorIndex].card_swatch_style = swatchStyle || product.colors[colorIndex].card_swatch_style || '';
                product.colors[colorIndex].swatch_image = swatchImage || product.colors[colorIndex].swatch_image || '';
                product.colors[colorIndex].hex = hex || product.colors[colorIndex].hex || '';
            });

            return product;
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
                className: cardSwatchClass($active) || $active.data('colorClass') || '',
                image: $active.data('colorImage') || '',
                swatchStyle: cardSwatchDisplayStyle($active),
                swatchImage: $active.data('colorSwatch') || $active.data('colorSwatchImage') || '',
                hex: $active.data('colorHex') || ''
            };
        }

        function syncCardSelectedColor($card, $swatch) {
            if (!$card.length || !$swatch.length) {
                return;
            }

            $card
                .attr('data-selected-color-id', $swatch.data('colorId') || '')
                .attr('data-selected-color-name', $swatch.data('colorName') || '')
                .attr('data-selected-color-code', $swatch.data('colorCode') || '')
                .attr('data-selected-color-class', $swatch.data('colorClass') || '')
                .attr('data-selected-color-image', $swatch.data('colorImage') || '');
        }

        function settleProductCardSkeleton($card) {
            if (!$card || !$card.length) {
                return;
            }

            var $imgs = $card.find('img.img-product');

            if (!$imgs.length) {
                $card.addClass('is-loaded');
                return;
            }

            if ($imgs.toArray().every(function (img) { return img.complete && img.naturalWidth > 0; })) {
                $card.addClass('is-loaded');
                return;
            }

            $imgs.each(function () {
                $(this).one('load error', function () {
                    $card.addClass('is-loaded');
                });
            });

            window.setTimeout(function () {
                $card.addClass('is-loaded');
            }, 1200);
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

        function renderGalleryMarkup(gallery) {
            var items = Array.isArray(gallery) && gallery.length ? gallery : [];
            var html = '';

            $.each(items, function (index, src) {
                if (!src) {
                    return;
                }

                html += '<div class="swiper-slide"><div class="item"><img src="' + escapeHtml(src) + '" alt=""></div></div>';
            });

            if (!html) {
                html = '<div class="swiper-slide"><div class="item"><img src="" alt=""></div></div>';
            }

            return html;
        }

        function readSelected($modal, name) {
            var $input = $modal.find('input[name="' + name + '"]:checked');
            return $input.length ? $input.val() : '';
        }

        function renderSizeChart($modal, product) {
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

        function syncQuickViewPricing($modal, product, selectedSize) {
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

            $modal.find('[data-qv-price-current]')
                .attr('data-base-price', totalCurrent)
                .attr('data-base-currency', currency)
                .text(currentLabel);

            $modal.find('[data-qv-submit-price]')
                .attr('data-base-price', totalCurrent)
                .attr('data-base-currency', currency)
                .text(currentLabel);

            $modal.find('[data-qv-price-old]')
                .attr('data-base-price', totalCompare)
                .attr('data-base-currency', currency)
                .text(compareLabel)
                .toggleClass('d-none', ! compareLabel);

            if (window.updateCurrencyConvertedPrices) {
                window.updateCurrencyConvertedPrices();
            }
        }

        function syncQuickAddPricing($modal, product, selectedSize) {
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

            $modal.find('[data-qadd-price]')
                .attr('data-base-price', totalCurrent)
                .attr('data-base-currency', currency)
                .text(currentLabel);

            $modal.find('[data-qadd-submit-price]')
                .attr('data-base-price', totalCurrent)
                .attr('data-base-currency', currency)
                .text(currentLabel);

            $modal.find('[data-qadd-price-old]')
                .attr('data-base-price', totalCompare)
                .attr('data-base-currency', currency)
                .text(compareLabel)
                .toggleClass('d-none', ! compareLabel);

            if (window.updateCurrencyConvertedPrices) {
                window.updateCurrencyConvertedPrices();
            }
        }

        function syncQuickViewSelection($modal) {
            var product = parseProduct($modal.data('product')) || {};
            var selectedColorRef = readSelected($modal, 'color');
            var colorData = findProductColor(product, selectedColorRef);
            var sizes = Array.isArray(colorData.size_options) && colorData.size_options.length
                ? colorData.size_options
                : (Array.isArray(product.size_options) && product.size_options.length
                    ? product.size_options
                    : (Array.isArray(colorData.sizes) && colorData.sizes.length
                        ? colorData.sizes
                        : (Array.isArray(product.sizes) ? product.sizes : [])));
            var selectedSize = readSelected($modal, 'size');
            var available = hasAvailableSize(sizes);
            var detailUrl = detailUrlWithColor(product, {
                id: colorData.id || '',
                code: colorData.color_code || '',
                name: colorData.name || selectedColorRef || ''
            });

            $modal.find('[data-qv-title]').attr('href', detailUrl).text(product.title || '');
            $modal.find('[data-qv-detail]').attr('href', detailUrl);
            $modal.find('[data-qv-badge]').toggleClass('d-none', ! product.badge).text(product.badge || '').attr('data-badge-class', product.badge_class || '');
            $modal.find('[data-qv-product-code]').text(product.product_code || '—');
            $modal.find('[data-qv-color-label]').text(colorData.name || selectedColorRef || '');
            $modal.find('[data-qv-color-code]').text(colorData.color_code || '—');
            $modal.find('[data-qv-description]').html(product.description ? '<p>' + escapeHtml(product.description) + '</p>' : '');
            $modal.find('[data-qv-gallery]').html(renderGalleryMarkup(colorData.gallery || product.gallery || [product.image || '']));
            $modal.find('[data-qv-sizes]').html(buildOptionMarkup(sizes, selectedSize, 'size'));

            selectedSize = readSelected($modal, 'size');
            $modal.find('[data-qv-size-label]').text(selectedSize || product.default_size || (sizes[0] && (sizes[0].name || sizes[0].label || sizes[0].size || sizes[0].value || sizes[0])) || '');
            $modal.find('[data-qv-find-size]').toggleClass('d-none', ! product.has_size_chart);
            $modal.find('[data-cart-submit]')
                .attr('data-cart-url', product.cart_add_url || '')
                .prop('disabled', ! available)
                .toggleClass('disabled', ! available)
                .attr('aria-disabled', ! available ? 'true' : 'false');

            syncQuickViewPricing($modal, product, selectedSize);
        }

        function syncQuickAddSelection($modal) {
            var product = parseProduct($modal.data('product')) || {};
            var selectedColorRef = readSelected($modal, 'color');
            var colorData = findProductColor(product, selectedColorRef);
            var sizes = Array.isArray(colorData.size_options) && colorData.size_options.length
                ? colorData.size_options
                : (Array.isArray(product.size_options) && product.size_options.length
                    ? product.size_options
                    : (Array.isArray(colorData.sizes) && colorData.sizes.length
                        ? colorData.sizes
                        : (Array.isArray(product.sizes) ? product.sizes : [])));
            var selectedSize = readSelected($modal, 'size');
            var available = hasAvailableSize(sizes);
            var detailUrl = detailUrlWithColor(product, {
                id: colorData.id || '',
                code: colorData.color_code || '',
                name: colorData.name || selectedColorRef || ''
            });

            $modal.find('[data-qadd-title-link]').attr('href', detailUrl).text(product.title || '');
            $modal.find('[data-qadd-image]').attr('src', colorData.image || (Array.isArray(colorData.gallery) && colorData.gallery[0]) || product.image || '');
            $modal.find('[data-qadd-price]').text(product.price_label || product.price_current_label || '');
            $modal.find('[data-qadd-color-label]').text(colorData.name || selectedColorRef || '');
            $modal.find('[data-qadd-size-label]').text(selectedSize || product.default_size || (sizes[0] && (sizes[0].name || sizes[0].label || sizes[0].size || sizes[0].value || sizes[0])) || '');
            $modal.find('[data-qadd-colors]').html(buildOptionMarkup(product.colors || [], selectedColorRef || product.default_color || '', 'color'));
            $modal.find('[data-qadd-sizes]').html(buildOptionMarkup(sizes, selectedSize, 'size'));

            selectedSize = readSelected($modal, 'size');
            $modal.find('[data-cart-submit]')
                .attr('data-cart-url', product.cart_add_url || '')
                .prop('disabled', ! available)
                .toggleClass('disabled', ! available)
                .attr('aria-disabled', ! available ? 'true' : 'false');

            syncQuickAddPricing($modal, product, selectedSize);
        }

        function renderQuickModal($modal, product, prefix, options) {
            options = options || {};
            $modal.data('product', product);
            $modal.find('input[name="number"]').val(1);

            var selectedDetailUrl = detailUrlWithColor(product, options.selectedColor || {});

            if (prefix === 'qv') {
                $modal.find('[data-qv-title]').attr('href', selectedDetailUrl).text(product.title || '');
                $modal.find('[data-qv-detail]').attr('href', selectedDetailUrl);
                $modal.find('[data-qv-description]').html(product.description ? '<p>' + escapeHtml(product.description) + '</p>' : '');
                $modal.find('[data-qv-badge]').toggleClass('d-none', ! product.badge).text(product.badge || '').attr('data-badge-class', product.badge_class || '');
                $modal.find('[data-qv-product-code]').text(product.product_code || '—');
                $modal.find('[data-qv-price-current]').text(product.price_label || product.price_current_label || '');
                $modal.find('[data-qv-submit-price]').text(product.price_label || product.price_current_label || '');
                $modal.find('[data-qv-gallery]').html(renderGalleryMarkup(product.gallery || [product.image || '']));
                $modal.find('[data-qv-colors]').html(buildOptionMarkup(product.colors || [], options.selectedColor ? (options.selectedColor.id || options.selectedColor.code || options.selectedColor.name || options.selectedColor.className || '') : (product.default_color || ''), 'color'));
                $modal.find('[data-qv-sizes]').html(buildOptionMarkup(product.size_options || product.sizes || [], product.default_size || '', 'size'));
                syncQuickViewSelection($modal);
                return;
            }

            $modal.find('[data-qadd-title-link]').attr('href', selectedDetailUrl).text(product.title || '');
            $modal.find('[data-qadd-image]').attr('src', product.image || '');
            $modal.find('[data-qadd-price]').text(product.price_label || product.price_current_label || '');
            $modal.find('[data-qadd-price-old]').text(product.compare_price_label || '').toggleClass('d-none', ! product.compare_price_label);
            $modal.find('[data-qadd-colors]').html(buildOptionMarkup(product.colors || [], options.selectedColor ? (options.selectedColor.id || options.selectedColor.code || options.selectedColor.name || options.selectedColor.className || '') : (product.default_color || ''), 'color'));
            $modal.find('[data-qadd-sizes]').html(buildOptionMarkup(product.size_options || product.sizes || [], product.default_size || '', 'size'));
            syncQuickAddSelection($modal);
        }

        function submitQuickModal($button) {
            var $modal = $button.closest('.modal');
            var product = parseProduct($modal.data('product')) || {};
            var cartUrl = $button.attr('data-cart-url') || product.cart_add_url || '';
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
            var product = hydrateProductColorsFromCard(parseProduct($card.data('product')), $card);
            if (!product) {
                return;
            }

            var selectedColor = getCardSelectedColor($card);
            if (selectedColor.name || selectedColor.code || selectedColor.className || selectedColor.id) {
                product.default_color = selectedColor.id || selectedColor.code || selectedColor.name || selectedColor.className;
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
            applyCardSwatch($swatch, true);
        });

        $(document).on('mouseenter', '.card-product .list-color-item.color-swatch', function () {
            applyCardSwatch($(this), true);
        });

        function applyCardSwatch($swatch, persistActive) {
            if (!$swatch.length) {
                return;
            }

            var $card = $swatch.closest('.card-product');
            var imageSrc = $swatch.data('colorImage') || $swatch.find('img').attr('src') || '';

            if (persistActive) {
                $card.find('.list-color-item.color-swatch').removeClass('active');
                $swatch.addClass('active');
                syncCardSelectedColor($card, $swatch);
            }

            if (imageSrc) {
                $card.find('.img-product').attr('src', imageSrc).attr('data-src', imageSrc);
            }

            updateCardDetailLinks($card);
        }

        $(function () {
            $('.card-product').each(function () {
                var $card = $(this);
                var $activeSwatch = $card.find('.list-color-item.color-swatch.active').first();

                if (! $activeSwatch.length) {
                    $activeSwatch = $card.find('.list-color-item.color-swatch').first();
                }

                if ($activeSwatch.length) {
                    syncCardSelectedColor($card, $activeSwatch);
                    updateCardDetailLinks($card);
                }

                settleProductCardSkeleton($card);
            });
        });

        $(document).on('click', '#quick_view [data-cart-submit], #quick_add [data-cart-submit]', function (event) {
            event.preventDefault();
            submitQuickModal($(this));
        });

        $(document).on('change', '#quick_view input[name="color"]', function () {
            syncQuickViewSelection($(this).closest('#quick_view'));
        });

        $(document).on('change', '#quick_view input[name="size"]', function () {
            syncQuickViewSelection($(this).closest('#quick_view'));
        });

        $(document).on('change input', '#quick_view input[name="number"]', function () {
            var $modal = $(this).closest('#quick_view');
            var product = parseProduct($modal.data('product')) || {};
            syncQuickViewPricing($modal, product, readSelected($modal, 'size'));
        });

        $(document).on('click', '#quick_view .btn-quantity', function () {
            var $modal = $(this).closest('#quick_view');
            setTimeout(function () {
                var product = parseProduct($modal.data('product')) || {};
                syncQuickViewPricing($modal, product, readSelected($modal, 'size'));
            }, 0);
        });

        $(document).on('change', '#quick_add input[name="color"]', function () {
            syncQuickAddSelection($(this).closest('#quick_add'));
        });

        $(document).on('change', '#quick_add input[name="size"]', function () {
            syncQuickAddSelection($(this).closest('#quick_add'));
        });

        $(document).on('change input', '#quick_add input[name="number"]', function () {
            var $modal = $(this).closest('#quick_add');
            var product = parseProduct($modal.data('product')) || {};
            syncQuickAddPricing($modal, product, readSelected($modal, 'size'));
        });

        $(document).on('click', '#quick_add .btn-quantity', function () {
            var $modal = $(this).closest('#quick_add');
            setTimeout(function () {
                var product = parseProduct($modal.data('product')) || {};
                syncQuickAddPricing($modal, product, readSelected($modal, 'size'));
            }, 0);
        });

        $(document).on('click', '#quick_view [data-qv-find-size]', function (event) {
            event.preventDefault();
            var $quickView = $('#quick_view');
            var product = parseProduct($quickView.data('product')) || {};
            renderSizeChart($('#find_size'), product);
            $('#find_size').modal('show');
        });

        var filterRequest = null;
        var filterRequestToken = 0;
        var filterDebounceTimer = null;
        var pendingCurrencyFilterSync = null;

        function shopProductsUrl() {
            return window.location.origin + window.location.pathname;
        }

        function currencyUpdateUrl() {
            return '{{ route('front.currency') }}';
        }

        function getCurrencyRate(currencyCode) {
            var code = String(currencyCode || '').toUpperCase();
            var $option = $('.js-currency-select').find('option[value="' + code + '"]').first();
            var rate = parseFloat($option.data('rate'));

            if (!rate || rate <= 0) {
                rate = 1;
            }

            return rate;
        }

        function convertFilterPriceBetweenCurrencies(amount, fromCurrency, toCurrency) {
            var numericAmount = parseFloat(amount);

            if (!isFinite(numericAmount)) {
                return 0;
            }

            var baseAmount = numericAmount * getCurrencyRate(fromCurrency);
            var converted = baseAmount / getCurrencyRate(toCurrency);

            return Math.max(0, Math.round(converted));
        }

        function buildStateQueryString($filterForm, $sortForm) {
            var params = new URLSearchParams();
            var sortValue = $sortForm.find('select[name="sort"]').val() || 'featured';
            var categories = [];
            var colors = [];
            var sizes = [];
            var minPrice = '';
            var maxPrice = '';

            $filterForm.find('input, select, textarea').each(function () {
                var $field = $(this);
                var name = $field.attr('name');

                if (!name || name === 'filter_ajax' || name === 'load_more' || name === 'page') {
                    return;
                }

                if ($field.is(':checkbox') || $field.is(':radio')) {
                    if (!$field.is(':checked')) {
                        return;
                    }
                }

                var value = $field.val();
                if (value === null || value === '') {
                    return;
                }

                if (name === 'category[]') {
                    categories.push(String(value));
                    return;
                }

                if (name === 'color[]') {
                    colors.push(String(value));
                    return;
                }

                if (name === 'size[]') {
                    sizes.push(String(value));
                    return;
                }

                if (name === 'min_price') {
                    minPrice = String(value);
                    return;
                }

                if (name === 'max_price') {
                    maxPrice = String(value);
                    return;
                }

                params.set(name, value);
            });

            if (categories.length) {
                params.set('categories', categories.join(','));
            }

            if (colors.length) {
                params.set('colors', colors.join(','));
            }

            if (sizes.length) {
                params.set('sizes', sizes.join(','));
            }

            if (minPrice !== '' || maxPrice !== '') {
                params.set('price', (minPrice || '0') + '-' + (maxPrice || minPrice || '0'));
            }

            params.set('sort', sortValue);

            return params.toString();
        }

        function refreshShopProducts(queryString, options) {
            options = options || {};

            var url = shopProductsUrl();
            var token = ++filterRequestToken;
            var requestUrl = url + (queryString ? ('?' + queryString + '&filter_ajax=1') : '?filter_ajax=1');

            if (filterRequest && filterRequest.readyState !== 4) {
                filterRequest.abort();
            }

            filterRequest = $.ajax({
                url: requestUrl,
                type: 'GET',
                dataType: 'json',
                headers: {
                    Accept: 'application/json'
                }
            }).done(function (response) {
                if (token !== filterRequestToken || !response) {
                    return;
                }

                if (response.toolbar_html) {
                    var $toolbar = $(response.toolbar_html);
                    $('[data-shop-toolbar]').replaceWith($toolbar);
                }

                if (response.filter_html) {
                    var $filter = $(response.filter_html);
                    var $existingFilter = $('[data-shop-filter]');
                    $existingFilter.find('.canvas-body').replaceWith($filter.find('.canvas-body'));
                }

                if (response.products_html) {
                    var $results = $(response.products_html);
                    $('[data-shop-results]').replaceWith($results);
                    $results.find('.card-product').each(function () {
                        var $card = $(this);
                        var $activeSwatch = $card.find('.list-color-item.color-swatch.active').first();

                        if (! $activeSwatch.length) {
                            $activeSwatch = $card.find('.list-color-item.color-swatch').first();
                        }

                        if ($activeSwatch.length) {
                            syncCardSelectedColor($card, $activeSwatch);
                            updateCardDetailLinks($card);
                        }

                        settleProductCardSkeleton($card);
                    });
                }

                if (typeof response.loadmore_html !== 'undefined') {
                    var $loadmore = $(response.loadmore_html || '<div data-shop-loadmore></div>');
                    $('[data-shop-loadmore]').replaceWith($loadmore);
                }

                if (options.pushState !== false) {
                    var historyUrl = url + (queryString ? ('?' + queryString) : '');
                    window.history.pushState({ queryString: queryString }, '', historyUrl);
                }

            });
        }

        function applyAjaxFilter(options) {
            var $filterForm = $('[data-filter-form]').first();
            var $sortForm = $('[data-sort-form]').first();

            if (!$filterForm.length || !$sortForm.length) {
                return;
            }

            var queryString = buildStateQueryString($filterForm, $sortForm);
            refreshShopProducts(queryString, options);
        }

        function debounceAjaxFilter(delay) {
            window.clearTimeout(filterDebounceTimer);
            filterDebounceTimer = window.setTimeout(function () {
                applyAjaxFilter();
            }, delay || 250);
        }

        $(function () {
            $('#filterShop')
                .find('input.tf-check-color, input.tf-check-size, .range-min, .range-max')
                .off('change');
        });

        $(document).on('submit', '[data-filter-form]', function (event) {
            event.preventDefault();
            applyAjaxFilter();
        });

        $(document).on('change', '[data-sort-form] select[name="sort"]', function () {
            applyAjaxFilter();
        });

        $(document).on('change', '[data-filter-form] input[name="category[]"], [data-filter-form] input[name="color[]"], [data-filter-form] input[name="size[]"]', function () {
            var $form = $(this).closest('[data-filter-form]');

            if ($(this).attr('name') === 'category[]') {
                $form.find('[data-reset-categories]').prop('checked', false);
            }

            debounceAjaxFilter(120);
        });

        $(document).on('input change', '[data-filter-form] .range-min, [data-filter-form] .range-max', function () {
            debounceAjaxFilter(250);
        });

        $(document).on('change', '[data-reset-categories]', function () {
            if (!$(this).is(':checked')) {
                return;
            }

            var $form = $(this).closest('[data-filter-form]');
            $form.find('input[name="category[]"]').prop('checked', false);
            debounceAjaxFilter(50);
        });

        $(document).on('click', '[data-filter-reset]', function (event) {
            event.preventDefault();

            var url = $(this).attr('href') || shopProductsUrl();
            var queryString = '';

            try {
                var parsed = new URL(url, window.location.origin);
                queryString = parsed.searchParams.toString();
            } catch (error) {
                queryString = '';
            }

            refreshShopProducts(queryString, { pushState: true });
        });

        $(document).on('click', '[data-filter-chip]', function (event) {
            event.preventDefault();

            var $filterForm = $('[data-filter-form]').first();
            var type = String($(this).data('filterChipType') || '');
            var value = String($(this).data('filterChipValue') || '');

            if (!$filterForm.length || !type) {
                return;
            }

            if (type === 'category') {
                $filterForm.find('input[name="category[]"][value="' + value.replace(/"/g, '\\"') + '"]').prop('checked', false);

                if ($filterForm.find('input[name="category[]"]:checked').length === 0) {
                    $filterForm.find('[data-reset-categories]').prop('checked', true);
                }
            } else if (type === 'color') {
                $filterForm.find('input[name="color[]"][value="' + value.replace(/"/g, '\\"') + '"]').prop('checked', false);
            } else if (type === 'size') {
                $filterForm.find('input[name="size[]"][value="' + value.replace(/"/g, '\\"') + '"]').prop('checked', false);
            } else if (type === 'price') {
                var $minField = $filterForm.find('.range-min');
                var $maxField = $filterForm.find('.range-max');

                $minField.val($minField.attr('min') || 0);
                $maxField.val($maxField.attr('max') || 0);
            }

            applyAjaxFilter();
        });

        window.addEventListener('popstate', function () {
            var queryString = window.location.search.replace(/^\?/, '');
            refreshShopProducts(queryString, { pushState: false });
        });

        $(document).on('change', '.js-currency-select', function () {
            pendingCurrencyFilterSync = {
                from: $(this).data('confirmed-currency') || $(this).find('option:selected').val() || '',
                to: $(this).val() || ''
            };
        });

        $(document).ajaxSuccess(function (event, xhr, settings) {
            if (!$('[data-filter-form]').length || !pendingCurrencyFilterSync) {
                return;
            }

            var requestUrl = String((settings && settings.url) || '');
            if (!requestUrl || requestUrl.indexOf(currencyUpdateUrl()) === -1) {
                return;
            }

            var $filterForm = $('[data-filter-form]').first();
            var fromCurrency = pendingCurrencyFilterSync.from || 'SYP';
            var toCurrency = pendingCurrencyFilterSync.to || fromCurrency;
            var $minField = $filterForm.find('.range-min');
            var $maxField = $filterForm.find('.range-max');

            if ($minField.length) {
                $minField.val(convertFilterPriceBetweenCurrencies($minField.val(), fromCurrency, toCurrency));
            }

            if ($maxField.length) {
                $maxField.val(convertFilterPriceBetweenCurrencies($maxField.val(), fromCurrency, toCurrency));
            }

            pendingCurrencyFilterSync = null;
            applyAjaxFilter();
        });

        $(document).on('click', '.btn-loadmore-ajax', function (event) {
            event.preventDefault();

            var $button = $(this);
            var url = appendQueryParam($button.data('loadmoreUrl') || $button.attr('href') || '', 'load_more', 1);
            var $grid = $('.wrapper-shop.loadmore-item').first();
            var $wrap = $button.closest('.view-more-button');

            if (!url || !$grid.length || $button.data('loading')) {
                return;
            }

            $button.data('loading', true).addClass('loading').attr('aria-busy', 'true').prop('disabled', true);

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                headers: {
                    Accept: 'application/json'
                }
            }).done(function (response) {
                if (response && response.html) {
                    var $newCards = $(response.html);
                    $grid.append($newCards);
                    $newCards.each(function () {
                        settleProductCardSkeleton($(this));
                    });
                }

                if (response && response.next_page_url) {
                    $button.data('loadmoreUrl', response.next_page_url);
                }

                if (!response || response.has_more === false || !response.next_page_url) {
                    $wrap.remove();
                }
            }).fail(function () {
                $button.data('loading', false).removeClass('loading').attr('aria-busy', 'false').prop('disabled', false);
            }).always(function () {
                if ($button.closest('body').length) {
                    $button.data('loading', false).removeClass('loading').attr('aria-busy', 'false').prop('disabled', false);
                }
            });
        });
    })(jQuery);
</script>
