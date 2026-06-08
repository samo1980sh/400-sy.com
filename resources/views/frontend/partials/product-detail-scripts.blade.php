<script>
    (function ($) {
        'use strict';

        var SELECTORS = {
            root: '[data-detail-product]',
            colorsWrap: '[data-detail-colors]',
            colorInput: 'input[name="detail_color"]',
            colorLabel: 'label.color-btn',
            sizesWrap: '[data-detail-sizes]',
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

            if (Array.isArray(color.size_options) && color.size_options.length) {
                return color.size_options;
            }

            if (Array.isArray(product.size_options) && product.size_options.length) {
                return product.size_options;
            }

            return [];
        }

        function selectedSize() {
            if (selectedSizeIndex < 0) {
                return null;
            }

            var size = currentSizes()[selectedSizeIndex] || null;

            return isSoldOut(size) ? null : size;
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
            var sizes = currentSizes();

            selectedSizeIndex = firstAvailableSizeIndex(sizes);

            $root.find(SELECTORS.sizesWrap).html(sizes.map(function (size, index) {
                var label = normalizeSizeLabel(size);
                var soldOut = isSoldOut(size);
                var checked = index === selectedSizeIndex && ! soldOut;

                if (! label) {
                    return '';
                }

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
                    '<label class="style-text', soldOut ? ' disabled' : '', '" for="detail-size-js-', index, '" data-value="', escapeHtml(label), '"', soldOut ? ' aria-disabled="true" data-size-unavailable="true"' : '', '>',
                        '<span class="size-label">', escapeHtml(label), '</span>',
                    '</label>'
                ].join('');
            }).join(''));
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
                return '<tr>' + columns.map(function (column) {
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

        $(document).on('click', SELECTORS.sizesWrap + ' label[aria-disabled="true"]', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
        });

        $(document).on('change', SELECTORS.sizesWrap + ' ' + SELECTORS.sizeInput, function () {
            var nextIndex = Number($(this).data('size-index') || 0);
            var size = currentSizes()[nextIndex] || null;

            if ($(this).is(':disabled') || isSoldOut(size)) {
                $(this).prop('checked', false);
                return;
            }

            selectedSizeIndex = nextIndex;
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

<script data-front-product-code-color-fix-03>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.__frontProductCodeColorFix03Initialized) {
            return;
        }

        window.__frontProductCodeColorFix03Initialized = true;

        const productCodeElement = document.querySelector('[data-detail-product-code]');

        if (! productCodeElement) {
            return;
        }

        const clean = function (value) {
            return String(value || '').trim();
        };

        const baseProductCode = clean(productCodeElement.dataset.baseProductCode || productCodeElement.getAttribute('data-base-product-code') || productCodeElement.textContent);

        productCodeElement.dataset.baseProductCode = baseProductCode;

        const formatProductCode = function (colorCode) {
            const base = clean(productCodeElement.dataset.baseProductCode || baseProductCode);
            const color = clean(colorCode);

            if (! base) {
                return '';
            }

            if (! color) {
                return base;
            }

            const suffix = '-' + color;

            if (base.toLowerCase().endsWith(suffix.toLowerCase())) {
                return base;
            }

            return base + suffix;
        };

        const resolveColorCodeFromInput = function (input) {
            if (! input) {
                return '';
            }

            const inputId = input.getAttribute('id') || '';
            let label = null;

            if (inputId !== '') {
                if (window.CSS && typeof window.CSS.escape === 'function') {
                    label = document.querySelector('label[for="' + window.CSS.escape(inputId) + '"]');
                } else {
                    label = document.querySelector('label[for="' + inputId.replace(/"/g, '\\"') + '"]');
                }
            }

            return clean((label && label.dataset ? label.dataset.colorCode : '') || input.dataset.colorCode || '');
        };

        const updateProductCode = function (colorCode) {
            productCodeElement.textContent = formatProductCode(colorCode);
        };

        const checkedColorInput = document.querySelector('input[name="detail_color"]:checked');
        const hiddenColorCodeElement = document.querySelector('[data-detail-color-code]');

        updateProductCode(resolveColorCodeFromInput(checkedColorInput) || (hiddenColorCodeElement ? hiddenColorCodeElement.textContent : ''));

        document.querySelectorAll('input[name="detail_color"]').forEach(function (input) {
            input.addEventListener('change', function () {
                const colorCode = resolveColorCodeFromInput(input);

                window.setTimeout(function () {
                    updateProductCode(colorCode);
                }, 0);
            });
        });
    });
</script>
