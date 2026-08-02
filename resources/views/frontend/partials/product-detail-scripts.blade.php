<script>
    (function ($) {
        'use strict';

        var SELECTORS = {
            root: '[data-detail-product]',
            colorsWrap: '[data-detail-colors]',
            colorInput: 'input[name="detail_color"]',
            colorLabel: 'label.color-btn',
            sizesWrap: '[data-detail-sizes]',
            sizeControls: '[data-detail-size-controls]',
            sizesEmpty: '[data-detail-sizes-empty]',
            sizeInput: 'input[name="detail_size"]',
            quantityInput: '[data-detail-quantity]',
            quantityButton: '[data-detail-qty]',
            cartForm: '[data-detail-cart-form]',
            cartSubmit: '[data-detail-cart-submit]',
            findSize: '[data-detail-find-size]',
            thumbsSlider: '[data-detail-thumbs-swiper]',
            mainSlider: '[data-detail-main-swiper]',
            thumbsWrapper: '[data-detail-thumbs]',
            galleryWrapper: '[data-detail-gallery]',
            lightboxGallery: '[data-detail-gallery-lightbox]'
        };

        var $root = $(SELECTORS.root).first();

        if (! $root.length) {
            return;
        }

        var product = parseProduct($root.data('detail-product')) || {};
        var colors = Array.isArray(product.colors) ? product.colors : [];
        var selectedColorIndex = parseInt($root.attr('data-detail-default-color-index'), 10) || 0;
        var selectedSizeIndex = 0;
        var initialColorSyncDone = false;
        var thumbsSwiper = null;
        var mainSwiper = null;

        /**
         * Generic helpers
         */
        function parseProduct(value) {
            if (! value) {
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

        function csrfToken() {
            return $('meta[name="csrf-token"]').attr('content') || '';
        }

        function queryParam(name) {
            try {
                return new URL(window.location.href).searchParams.get(name) || '';
            } catch (error) {
                return '';
            }
        }

        function sameText(first, second) {
            return String(first || '').trim().toLowerCase() === String(second || '').trim().toLowerCase();
        }

        function normalizeColor(value) {
            return String(value || '').trim().toLowerCase();
        }

        function clampQuantity(value) {
            var quantity = parseInt(value, 10) || 1;

            return Math.max(1, Math.min(99, quantity));
        }

        function showModal(selector) {
            var $modal = $(selector);

            if (! $modal.length) {
                return;
            }

            if (typeof $modal.modal === 'function') {
                $modal.modal('show');
                return;
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance($modal[0]).show();
            }
        }

        /**
         * Product state helpers
         */
        function currentColor() {
            return colors[selectedColorIndex] || colors[0] || {};
        }

        function currentSizes() {
            var color = currentColor();

            return Array.isArray(color.size_options)
                ? color.size_options
                : [];
        }

        function selectedSize() {
            var size = currentSizes()[selectedSizeIndex] || null;

            if (isSoldOut(size)) {
                return null;
            }

            return size;
        }

        function normalizeSizeLabel(item) {
            if (! item) {
                return '';
            }

            if (typeof item === 'string') {
                return item;
            }

            return item.size || item.name || item.label || item.value || '';
        }

        function isSoldOut(item) {
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

        function firstAvailableSizeIndex(sizes) {
            for (var index = 0; index < sizes.length; index += 1) {
                if (normalizeSizeLabel(sizes[index]) && ! isSoldOut(sizes[index])) {
                    return index;
                }
            }

            return -1;
        }

        function selectedQuantity() {
            return clampQuantity($root.find(SELECTORS.quantityInput).val());
        }

        function currentBaseCurrency() {
            return product.base_currency || $('.js-currency-select').val() || 'SYP';
        }

        function formatTotal(price, quantity) {
            var currency = currentBaseCurrency();
            var number = Number(price || 0) * Math.max(1, quantity || 1);

            if (! number) {
                return '';
            }

            return Math.round(number).toLocaleString() + ' ' + currency;
        }

        /**
         * Initial color resolution and URL sync
         */
        function initialColorIndexFromUrl() {
            var requestedId = queryParam('color_id') || queryParam('product_color_id');
            var requestedCode = queryParam('color_code');
            var requestedName = queryParam('color');

            if (! requestedId && ! requestedCode && ! requestedName) {
                return selectedColorIndex >= 0 && selectedColorIndex < colors.length ? selectedColorIndex : 0;
            }

            for (var index = 0; index < colors.length; index += 1) {
                var color = colors[index] || {};

                if (requestedId && String(color.id || '') === String(requestedId)) {
                    return index;
                }

                if (requestedCode && sameText(color.color_code, requestedCode)) {
                    return index;
                }

                if (requestedName && sameText(color.name, requestedName)) {
                    return index;
                }
            }

            return selectedColorIndex >= 0 && selectedColorIndex < colors.length ? selectedColorIndex : 0;
        }

        function syncColorInputs() {
            var $input = $root.find(
                SELECTORS.colorsWrap + ' ' + SELECTORS.colorInput + '[data-color-index="' + selectedColorIndex + '"]'
            );

            if ($input.length) {
                $input.prop('checked', true);
            }
        }

        function updateSelectedColorUrl() {
            var color = currentColor();

            if (! color || ! window.history || ! window.history.replaceState) {
                return;
            }

            try {
                var url = new URL(window.location.href);

                if (color.id) {
                    url.searchParams.set('color_id', color.id);
                } else {
                    url.searchParams.delete('color_id');
                }

                if (color.color_code) {
                    url.searchParams.set('color_code', color.color_code);
                } else {
                    url.searchParams.delete('color_code');
                }

                if (color.name) {
                    url.searchParams.set('color', color.name);
                } else {
                    url.searchParams.delete('color');
                }

                window.history.replaceState({}, '', url.toString());
            } catch (error) {
                // Keep the page usable on older browsers.
            }
        }

        /**
         * Product gallery / Swiper handling
         */
        function galleryScope() {
            return {
                $thumbsSlider: $root.find(SELECTORS.thumbsSlider).first(),
                $mainSlider: $root.find(SELECTORS.mainSlider).first(),
                $thumbSlides: $root.find(SELECTORS.thumbsWrapper + ' .swiper-slide'),
                $mainSlides: $root.find(SELECTORS.galleryWrapper + ' .swiper-slide')
            };
        }

        function getVisibleThumbSlides($slides) {
            return $slides.filter(function () {
                return $(this).css('display') !== 'none';
            });
        }

        function syncThumbActive($thumbSlides, visibleIndex) {
            $thumbSlides.removeClass('swiper-slide-thumb-active');

            var $visibleThumb = getVisibleThumbSlides($thumbSlides).eq(visibleIndex);

            if ($visibleThumb.length) {
                $visibleThumb.addClass('swiper-slide-thumb-active');
            }
        }

        function initScopedGallery() {
            if (typeof Swiper === 'undefined') {
                return;
            }

            var scope = galleryScope();

            if (! scope.$thumbsSlider.length || ! scope.$mainSlider.length) {
                return;
            }

            if (! thumbsSwiper) {
                thumbsSwiper = new Swiper(scope.$thumbsSlider.get(0), {
                    spaceBetween: 10,
                    slidesPerView: 'auto',
                    freeMode: true,
                    direction: 'vertical',
                    watchSlidesProgress: true,
                    observer: true,
                    observeParents: true,
                    breakpoints: {
                        0: {
                            direction: 'horizontal',
                            slidesPerView: 5
                        },
                        1150: {
                            direction: scope.$thumbsSlider.data('direction') || 'vertical'
                        }
                    }
                });
            }

            if (! mainSwiper) {
                mainSwiper = new Swiper(scope.$mainSlider.get(0), {
                    spaceBetween: 0,
                    observer: true,
                    observeParents: true,
                    navigation: {
                        nextEl: scope.$mainSlider.find('.thumbs-next').get(0),
                        prevEl: scope.$mainSlider.find('.thumbs-prev').get(0)
                    },
                    thumbs: {
                        swiper: thumbsSwiper
                    }
                });

                mainSwiper.on('slideChange', function () {
                    syncThumbActive(galleryScope().$thumbSlides, this.activeIndex || 0);
                });
            }

            thumbsSwiper.update();
            mainSwiper.update();
        }

        function applyGalleryColorFilter() {
            var color = currentColor();
            var normalized = normalizeColor(color.name || '');
            var scope = galleryScope();

            if (! normalized || ! scope.$thumbSlides.length || ! scope.$mainSlides.length) {
                return;
            }

            scope.$thumbSlides.each(function () {
                var $slide = $(this);
                $slide.css('display', normalizeColor($slide.data('color')) === normalized ? '' : 'none');
            });

            scope.$mainSlides.each(function () {
                var $slide = $(this);
                $slide.css('display', normalizeColor($slide.data('color')) === normalized ? '' : 'none');
            });

            initScopedGallery();

            if (mainSwiper && typeof mainSwiper.slideTo === 'function') {
                mainSwiper.slideTo(0, 0, false);
            }

            if (thumbsSwiper && typeof thumbsSwiper.slideTo === 'function') {
                thumbsSwiper.slideTo(0, 0, false);
            }

            syncThumbActive(galleryScope().$thumbSlides, 0);

            if (typeof window.initProductDetailPhotoSwipe === 'function') {
                window.initProductDetailPhotoSwipe();
            }
        }

        function detachGlobalTemplateGalleryHandlers() {
            $root.find(SELECTORS.colorLabel).off('click');
            $root.find('.tf-product-media-thumbs').off('click', '.swiper-slide');
        }

        function bindDetailColorClicks() {
            $root.find(SELECTORS.colorsWrap + ' ' + SELECTORS.colorLabel).each(function () {
                var label = this;

                if (label.dataset.detailColorBound === '1') {
                    return;
                }

                label.dataset.detailColorBound = '1';

                label.addEventListener('click', function (event) {
                    var inputId = label.getAttribute('for');
                    var input = inputId ? document.getElementById(inputId) : null;

                    if (! input) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();

                    if (! input.checked) {
                        input.checked = true;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }, true);
            });
        }

        function syncTemplateColorSelection() {
            var $label = $root.find(SELECTORS.colorsWrap + ' ' + SELECTORS.colorLabel).eq(selectedColorIndex);

            if (! $label.length) {
                return;
            }

            if (! initialColorSyncDone) {
                initialColorSyncDone = true;
                window.setTimeout(function () {
                    applyGalleryColorFilter();
                }, 0);
                return;
            }

            $root.find(SELECTORS.colorsWrap + ' ' + SELECTORS.colorLabel).removeClass('active');
            $label.addClass('active');
            applyGalleryColorFilter();
        }

        /**
         * Sizes, price, labels
         */
        function renderSizes() {
            var sizes = currentSizes().filter(function (size) {
                return normalizeSizeLabel(size) !== '';
            });
            var hasSizes = sizes.length > 0;

            selectedSizeIndex = hasSizes ? firstAvailableSizeIndex(sizes) : -1;

            $root.find(SELECTORS.sizeControls).toggleClass('d-none', ! hasSizes);
            $root.find(SELECTORS.sizesEmpty).toggleClass('d-none', hasSizes);

            $root.find(SELECTORS.sizesWrap).html(sizes.map(function (size, index) {
                var label = normalizeSizeLabel(size);
                var soldOut = isSoldOut(size);
                var checked = index === selectedSizeIndex && ! soldOut;

                return [
                    '<input type="radio" name="detail_size" id="detail-size-js-', index, '" value="', escapeHtml(label), '" ',
                        'data-size-index="', index, '" ',
                        'data-size-id="', escapeHtml(size.size_id || ''), '" ',
                        'data-size-code="', escapeHtml(size.size_code || ''), '" ',
                        'data-variant-id="', escapeHtml(size.variant_id || ''), '" ',
                        'data-product-color-id="', escapeHtml(size.product_color_id || ''), '"',
                        checked ? ' checked' : '',
                        soldOut ? ' disabled' : '',
                    '>',
                    soldOut
                        ? '<span class="style-text disabled" data-value="' + escapeHtml(label) + '" aria-disabled="true">'
                        : '<label class="style-text" for="detail-size-js-' + index + '" data-value="' + escapeHtml(label) + '">',
                        '<span class="size-label">', escapeHtml(label), '</span>',
                    soldOut ? '</span>' : '</label>'
                ].join('');
            }).join(''));
        }

        function whatsappDataValue($element, key) {
            var value = $element.attr('data-' + key) || '';

            return String(value || '').trim();
        }

        function buildWhatsappInquiryText($link, color) {
            var intro = whatsappDataValue($link, 'whatsapp-intro') || 'مرحبًا، أود الاستفسار عن المنتج:';
            var productLabel = whatsappDataValue($link, 'whatsapp-product-label') || 'رمز المنتج';
            var colorLabel = whatsappDataValue($link, 'whatsapp-color-label') || 'رمز اللون';
            var productCode = whatsappDataValue($link, 'whatsapp-product-code');
            var colorCode = String((color && color.color_code) || '').trim();
            var lines = [intro];

            if (productCode) {
                lines.push(productLabel + ': ' + productCode);
            }

            if (colorCode) {
                lines.push(colorLabel + ': ' + colorCode);
            }

            return lines.join('\n');
        }

        function syncWhatsappInquiryLink(color) {
            var $link = $root.find('[data-detail-whatsapp-inquiry]').first();

            if (! $link.length) {
                return;
            }

            var phone = whatsappDataValue($link, 'whatsapp-phone').replace(/[^0-9]/g, '');

            if (! phone) {
                return;
            }

            $link.attr('href', 'https://wa.me/' + phone + '?text=' + encodeURIComponent(buildWhatsappInquiryText($link, color || currentColor())));
        }

        function syncPriceAndLabels() {
            var color = currentColor();
            var size = selectedSize();
            var quantity = selectedQuantity();
            var currentPrice = (size && size.price_current) || color.price_current || product.price_current || product.base_price || 0;
            var comparePrice = (size && size.compare_price) || color.compare_price || product.compare_price || 0;
            var currentLabel = (size && size.price_current_label) || color.price_current_label || product.price_current_label || product.price_label || '';
            var compareLabel = (size && size.compare_price_label) || color.compare_price_label || product.compare_price_label || '';
            var currency = currentBaseCurrency();
            var sizeLabel = normalizeSizeLabel(size);

            $root.find('[data-detail-current-price]')
                .text(currentLabel)
                .attr('data-base-price', currentPrice || 0)
                .attr('data-base-currency', currency);

            $root.find('[data-detail-submit-price]')
                .text(formatTotal(currentPrice, quantity) || currentLabel)
                .attr('data-base-price', currentPrice || 0)
                .attr('data-base-currency', currency);

            $root.find('[data-detail-compare-price]')
                .text(compareLabel || '')
                .attr('data-base-price', comparePrice || 0)
                .attr('data-base-currency', currency)
                .toggleClass('d-none', ! compareLabel);

            $root.find('[data-detail-color-label]').text(color.name || '');
            $root.find('[data-detail-color-code]').text(color.color_code || '');
            $root.find('[data-detail-size-label]').text(sizeLabel || '');
            syncWhatsappInquiryLink(color);

            if (window.updateCurrencyConvertedPrices) {
                window.updateCurrencyConvertedPrices();
            }
        }

        /**
         * Size chart modal
         */
        function renderSizeChart() {
            var $modal = $('#find_size');

            if (! $modal.length) {
                return;
            }

            var chart = product.size_chart || {};
            var rows = Array.isArray(chart.rows) ? chart.rows : [];
            var columns = Array.isArray(chart.columns) ? chart.columns : [];
            var guideImage = String(chart.guide_image || '').trim();
            var $table = $modal.find('[data-size-chart-table]');
            var $head = $modal.find('[data-size-chart-head]');
            var $body = $modal.find('[data-size-chart-body]');
            var $empty = $modal.find('[data-size-chart-empty]');
            var $guideWrap = $modal.find('[data-size-chart-guide-wrap]');
            var $guideImage = $modal.find('[data-size-chart-guide-image]');
            var $tableWrap = $modal.find('[data-size-chart-table-wrap]');

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

            renderSizeFinder(columns, rows);

            if (! rows.length || ! columns.length) {
                $table.addClass('d-none');
                $empty.removeClass('d-none');
                $head.empty();
                $body.empty();
                return;
            }

            $head.html(columns.map(function (column) {
                return '<th>' + escapeHtml(column.label || '') + '</th>';
            }).join(''));

            $body.html(rows.map(function (row) {
                var rowSize = String(row.size_code || row.size || '').trim();

                return '<tr data-size-chart-row-size="' + escapeHtml(rowSize) + '">' + columns.map(function (column) {
                    var value = row[column.key];

                    if (value === null || typeof value === 'undefined' || value === '') {
                        value = '-';
                    }

                    return '<td>' + escapeHtml(value) + '</td>';
                }).join('') + '</tr>';
            }).join(''));

            $empty.addClass('d-none');
            $table.removeClass('d-none');
        }

        function sizeFinderText(key, fallback) {
            var $panel = $('#find_size').find('[data-size-finder-panel]').first();
            var value = $panel.data(key);

            return typeof value === 'undefined' || value === null || value === '' ? fallback : String(value);
        }

        function normalizeMeasurementNumber(value) {
            if (value === null || typeof value === 'undefined') {
                return null;
            }

            var normalized = String(value || '').trim();

            if (! normalized) {
                return null;
            }

            normalized = normalized.replace(/[٠-٩]/g, function (digit) {
                return '٠١٢٣٤٥٦٧٨٩'.indexOf(digit);
            }).replace(/[۰-۹]/g, function (digit) {
                return '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit);
            }).replace(',', '.');

            var match = normalized.match(/-?\d+(?:\.\d+)?/);

            if (! match) {
                return null;
            }

            var number = parseFloat(match[0]);

            return Number.isFinite(number) && number > 0 ? number : null;
        }

        function measurementFields(columns, rows) {
            return columns.filter(function (column) {
                if (! column || column.key === 'size_code') {
                    return false;
                }

                return rows.some(function (row) {
                    return normalizeMeasurementNumber(row[column.key]) !== null;
                });
            });
        }

        function renderSizeFinder(columns, rows) {
            var $modal = $('#find_size');
            var $panel = $modal.find('[data-size-finder-panel]');
            var $fields = $modal.find('[data-size-finder-fields]');
            var $result = $modal.find('[data-size-finder-result]');
            var fields = measurementFields(columns, rows);
            var suffix = sizeFinderText('fieldSuffix', 'cm');

            $result.addClass('d-none').empty();

            if (! rows.length || ! fields.length) {
                $panel.addClass('d-none');
                $fields.empty();
                return;
            }

            $panel.removeClass('d-none');
            $fields.html(fields.map(function (field) {
                return [
                    '<div class="size-finder-field">',
                        '<label for="size-finder-', escapeHtml(field.key), '">', escapeHtml(field.label || field.key), '</label>',
                        '<input type="number" inputmode="decimal" min="0" step="0.1" ',
                            'id="size-finder-', escapeHtml(field.key), '" ',
                            'data-size-finder-input="', escapeHtml(field.key), '" ',
                            'placeholder="', escapeHtml(suffix), '">',
                    '</div>'
                ].join('');
            }).join(''));
        }

        function collectSizeFinderInputs() {
            var values = {};

            $('#find_size').find('[data-size-finder-input]').each(function () {
                var key = String($(this).data('size-finder-input') || '').trim();
                var value = normalizeMeasurementNumber($(this).val());

                if (key && value !== null) {
                    values[key] = value;
                }
            });

            return values;
        }

        function suggestSizeFromMeasurements(values) {
            var chart = product.size_chart || {};
            var rows = Array.isArray(chart.rows) ? chart.rows : [];
            var columns = Array.isArray(chart.columns) ? chart.columns : [];
            var fields = measurementFields(columns, rows).filter(function (field) {
                return Object.prototype.hasOwnProperty.call(values, field.key);
            });

            if (! rows.length || ! fields.length) {
                return null;
            }

            var candidates = rows.map(function (row, index) {
                var score = 0;
                var tightCount = 0;
                var missingCount = 0;
                var matchedCount = 0;

                fields.forEach(function (field) {
                    var wanted = values[field.key];
                    var available = normalizeMeasurementNumber(row[field.key]);

                    if (wanted === null || available === null) {
                        missingCount += 1;
                        score += 10000;
                        return;
                    }

                    matchedCount += 1;

                    var room = available - wanted;

                    if (room >= 0) {
                        score += room;
                    } else {
                        tightCount += 1;
                        score += Math.abs(room) * 4 + 1000;
                    }
                });

                return {
                    row: row,
                    index: index,
                    size: String(row.size_code || row.size || '').trim(),
                    score: score,
                    tightCount: tightCount,
                    missingCount: missingCount,
                    matchedCount: matchedCount
                };
            }).filter(function (candidate) {
                return candidate.size && candidate.matchedCount > 0;
            });

            if (! candidates.length) {
                return null;
            }

            candidates.sort(function (first, second) {
                if (first.tightCount !== second.tightCount) {
                    return first.tightCount - second.tightCount;
                }

                if (first.missingCount !== second.missingCount) {
                    return first.missingCount - second.missingCount;
                }

                if (first.score !== second.score) {
                    return first.score - second.score;
                }

                return first.index - second.index;
            });

            return candidates[0];
        }

        function highlightSuggestedSize(size) {
            var normalized = normalizeSizeValue(size);
            var $modal = $('#find_size');

            $modal.find('[data-size-chart-row-size]').removeClass('is-recommended');

            if (! normalized) {
                return;
            }

            $modal.find('[data-size-chart-row-size]').each(function () {
                if (normalizeSizeValue($(this).data('size-chart-row-size')) === normalized) {
                    $(this).addClass('is-recommended');
                }
            });
        }

        function normalizeSizeValue(value) {
            return String(value || '').trim().toLowerCase().replace(/\s+/g, '');
        }

        function selectDetailSizeByLabel(size) {
            var normalized = normalizeSizeValue(size);
            var $inputs = $root.find(SELECTORS.sizesWrap + ' ' + SELECTORS.sizeInput);
            var $target = $();

            if (! normalized || ! $inputs.length) {
                return false;
            }

            $inputs.each(function () {
                var $input = $(this);
                var values = [
                    $input.val(),
                    $input.data('size-code'),
                    $input.closest('.variant-picker-values').find('label[for="' + $input.attr('id') + '"] .size-label').text()
                ];

                var matched = values.some(function (value) {
                    return normalizeSizeValue(value) === normalized;
                });

                if (matched && ! this.disabled && ! $target.length) {
                    $target = $input;
                }
            });

            if (! $target.length) {
                return false;
            }

            $target.prop('checked', true).trigger('change');

            return true;
        }

        function showSizeFinderMessage(message, type, size) {
            var $result = $('#find_size').find('[data-size-finder-result]');
            var safeSize = size ? String(size) : '';
            var html = '<div>' + escapeHtml(message || '') + (safeSize ? ' <span class="size-finder-result__size">' + escapeHtml(safeSize) + '</span>' : '') + '</div>';

            if (safeSize) {
                html += '<button type="button" class="size-finder-result__select" data-size-finder-select="' + escapeHtml(safeSize) + '">' + escapeHtml(sizeFinderText('select', 'Select this size')) + '</button>';
                html += '<span class="size-finder-result__note">' + escapeHtml(sizeFinderText('note', 'This suggestion is guidance.')) + '</span>';
            }

            $result
                .removeClass('d-none')
                .attr('data-size-finder-result-type', type || 'info')
                .html(html);
        }

        /**
         * Cart integration
         */
        function syncCartState(response) {
            if (! response) {
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
                    $subtotal
                        .text($newSubtotal.text())
                        .attr('data-base-price', $newSubtotal.attr('data-base-price') || 0)
                        .attr('data-base-currency', $newSubtotal.attr('data-base-currency') || $('.js-currency-select').val() || '');
                }

                if (window.updateCurrencyConvertedPrices) {
                    window.updateCurrencyConvertedPrices();
                }
            }
        }

        function submitDetailCart($form) {
            var url = String($form.data('cart-url') || '');
            var color = currentColor();
            var $selectedSize = $root.find(SELECTORS.sizesWrap + ' ' + SELECTORS.sizeInput + ':checked').first();
            var data = {
                quantity: selectedQuantity(),
                color: color.name || '',
                color_name: color.name || '',
                color_id: color.id || '',
                color_code: color.color_code || ''
            };

            if (! url) {
                return;
            }

            if ($selectedSize.length) {
                data.size = $selectedSize.val() || '';
                data.size_id = $selectedSize.data('sizeId') || '';
                data.size_code = $selectedSize.data('sizeCode') || '';
                data.variant_id = $selectedSize.data('variantId') || '';
            }

            $root.find(SELECTORS.cartSubmit).prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json'
                }
            }).done(function (response) {
                syncCartState(response || {});
                showModal('#shoppingCart');
            }).fail(function (xhr) {
                if (window.console) {
                    console.warn('Detail add-to-cart failed', xhr);
                }
            }).always(function () {
                $root.find(SELECTORS.cartSubmit).prop('disabled', false);
            });
        }

        function bindDisabledSizeGuards() {
            var rootElement = $root.get(0);

            if (! rootElement || rootElement.dataset.detailDisabledSizeGuard === '1') {
                return;
            }

            rootElement.dataset.detailDisabledSizeGuard = '1';

            rootElement.addEventListener('click', function (event) {
                var target = event.target;

                if (! target || ! target.closest) {
                    return;
                }

                var disabledSize = target.closest('[data-detail-sizes] [aria-disabled="true"], [data-detail-sizes] .style-text.disabled');

                if (! disabledSize || ! rootElement.contains(disabledSize)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                if (typeof event.stopImmediatePropagation === 'function') {
                    event.stopImmediatePropagation();
                }
            }, true);
        }

        /**
         * Events
         */
        $(document).on('change', SELECTORS.colorsWrap + ' ' + SELECTORS.colorInput, function () {
            selectedColorIndex = Number($(this).data('color-index') || 0);
            selectedSizeIndex = 0;
            syncColorInputs();
            updateSelectedColorUrl();
            syncTemplateColorSelection();
            renderSizes();
            syncPriceAndLabels();
        });

        $(document).on('change', SELECTORS.sizesWrap + ' ' + SELECTORS.sizeInput, function () {
            var nextSizeIndex = Number($(this).data('size-index') || 0);
            var nextSize = currentSizes()[nextSizeIndex] || null;

            if (this.disabled || isSoldOut(nextSize)) {
                $(this).prop('checked', false);
                syncPriceAndLabels();
                return;
            }

            selectedSizeIndex = nextSizeIndex;
            syncPriceAndLabels();
        });

        $(document).on('click', SELECTORS.quantityButton, function () {
            var $quantity = $root.find(SELECTORS.quantityInput);
            var current = parseInt($quantity.val(), 10) || 1;
            var next = $(this).data('detail-qty') === 'decrease'
                ? Math.max(1, current - 1)
                : Math.min(99, current + 1);

            $quantity.val(next);
            syncPriceAndLabels();
        });

        $(document).on('change keyup', SELECTORS.quantityInput, function () {
            $(this).val(selectedQuantity());
            syncPriceAndLabels();
        });

        $(document).on('click', SELECTORS.findSize, function (event) {
            event.preventDefault();
            renderSizeChart();
            showModal('#find_size');
        });

        $(document).on('click', '[data-size-finder-submit]', function (event) {
            event.preventDefault();

            var values = collectSizeFinderInputs();
            var hasValues = Object.keys(values).length > 0;

            if (! hasValues) {
                highlightSuggestedSize('');
                showSizeFinderMessage(sizeFinderText('empty', 'Enter at least one value to get a suggestion.'), 'warning');
                return;
            }

            var suggestion = suggestSizeFromMeasurements(values);

            if (! suggestion) {
                highlightSuggestedSize('');
                showSizeFinderMessage(sizeFinderText('unavailable', 'There are not enough measurement fields for this product.'), 'warning');
                return;
            }

            highlightSuggestedSize(suggestion.size);
            showSizeFinderMessage(
                suggestion.tightCount > 0 ? sizeFinderText('nearestPrefix', 'The closest size based on your values is') : sizeFinderText('resultPrefix', 'Your best suggested size is'),
                suggestion.tightCount > 0 ? 'nearest' : 'recommended',
                suggestion.size
            );
        });

        $(document).on('click', '[data-size-finder-reset]', function (event) {
            event.preventDefault();

            var $modal = $('#find_size');

            $modal.find('[data-size-finder-input]').val('');
            $modal.find('[data-size-finder-result]').addClass('d-none').empty();
            $modal.find('[data-size-chart-row-size]').removeClass('is-recommended');
        });

        $(document).on('click', '[data-size-finder-select]', function (event) {
            event.preventDefault();

            var size = $(this).data('size-finder-select') || '';
            var selected = selectDetailSizeByLabel(size);

            showSizeFinderMessage(
                selected ? sizeFinderText('selected', 'The suggested size has been selected.') : sizeFinderText('notSelectable', 'The suggested size is not currently available to select.'),
                selected ? 'selected' : 'warning',
                selected ? size : ''
            );
        });

        $(document).on('submit', SELECTORS.cartForm, function (event) {
            event.preventDefault();
            submitDetailCart($(this));
        });

        $(document).on('click', SELECTORS.thumbsWrapper + ' .swiper-slide', function (event) {
            var visibleIndex = getVisibleThumbSlides(galleryScope().$thumbSlides).index($(this));

            if (visibleIndex < 0) {
                return;
            }

            event.preventDefault();

            if (mainSwiper && typeof mainSwiper.slideTo === 'function') {
                mainSwiper.slideTo(visibleIndex, 400, false);
            }

            if (thumbsSwiper && typeof thumbsSwiper.slideTo === 'function') {
                thumbsSwiper.slideTo(visibleIndex, 400, false);
            }

            syncThumbActive(galleryScope().$thumbSlides, visibleIndex);
        });

        /**
         * Boot
         */
        selectedColorIndex = initialColorIndexFromUrl();
        detachGlobalTemplateGalleryHandlers();
        bindDisabledSizeGuards();
        bindDetailColorClicks();
        initScopedGallery();
        syncColorInputs();
        renderSizeChart();
        renderSizes();
        syncPriceAndLabels();
        syncTemplateColorSelection();
    })(jQuery);
</script>

<script type="module">
    import PhotoSwipeLightbox from "{{ asset('js/photoswipe-lightbox.esm.min.js') }}";
    import PhotoSwipe from "{{ asset('js/photoswipe.esm.min.js') }}";

    let productDetailLightbox = null;

    window.initProductDetailPhotoSwipe = function () {
        var galleryElement = document.querySelector('[data-detail-gallery-lightbox]');

        if (productDetailLightbox && typeof productDetailLightbox.destroy === 'function') {
            productDetailLightbox.destroy();
            productDetailLightbox = null;
        }

        if (! galleryElement) {
            return;
        }

        productDetailLightbox = new PhotoSwipeLightbox({
            gallery: galleryElement,
            children: 'a.item',
            pswpModule: PhotoSwipe,
            bgOpacity: 1,
            secondaryZoomLevel: 2,
            maxZoomLevel: 3
        });

        productDetailLightbox.init();
    };

    window.initProductDetailPhotoSwipe();
</script>
