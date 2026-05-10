<script>
    (function ($) {
        'use strict';

        var $root = $('[data-detail-product]').first();

        if (! $root.length) {
            return;
        }

        var product = parseProduct($root.data('detail-product')) || {};
        var colors = Array.isArray(product.colors) ? product.colors : [];
        var selectedColorIndex = parseInt($root.attr('data-detail-default-color-index'), 10) || 0;
        var selectedSizeIndex = 0;
        var thumbsSwiper = null;
        var mainSwiper = null;
        var initialFallbackImage = $root.find('[data-detail-gallery] img').first().attr('src') || product.image || '';

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
            var $input = $('[data-detail-colors] input[name="detail_color"][data-color-index="' + selectedColorIndex + '"]');

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

        function selectedSize() {
            return currentSizes()[selectedSizeIndex] || null;
        }

        function csrfToken() {
            return $('meta[name="csrf-token"]').attr('content') || '';
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

        function destroySwiperInstance(instance) {
            if (instance && typeof instance.destroy === 'function' && ! instance.destroyed) {
                instance.destroy(true, true);
            }
        }

        function cleanSwiperElement(element) {
            if (! element) {
                return;
            }

            if (element.swiper) {
                destroySwiperInstance(element.swiper);
                element.swiper = null;
            }

            element.classList.remove('swiper-initialized', 'swiper-horizontal', 'swiper-vertical', 'swiper-backface-hidden');
            element.removeAttribute('style');

            $(element).find('.swiper-wrapper, .swiper-slide').removeAttr('style');
        }

        function destroyGallery() {
            var thumbsElement = document.querySelector('[data-detail-thumbs-swiper]');
            var mainElement = document.querySelector('[data-detail-main-swiper]');

            destroySwiperInstance(mainSwiper);
            destroySwiperInstance(thumbsSwiper);
            mainSwiper = null;
            thumbsSwiper = null;

            cleanSwiperElement(mainElement);
            cleanSwiperElement(thumbsElement);
        }

        function buildThumbSlidesHtml(images, color) {
            var title = escapeHtml(product.title || '');
            var colorName = escapeHtml(color.name || '');
            return images.map(function (image, index) {
                var safeImage = escapeHtml(image);

                return [
                    '<div class="swiper-slide stagger-item" data-color="', colorName, '" data-detail-thumb-slide="', index, '">',
                        '<div class="item">',
                            '<img class="lazyloaded" data-src="', safeImage, '" src="', safeImage, '" alt="', title, '">',
                        '</div>',
                    '</div>'
                ].join('');
            }).join('');
        }

        function buildMainSlidesHtml(images, color) {
            var title = escapeHtml(product.title || '');
            var colorName = escapeHtml(color.name || '');

            return images.map(function (image, index) {
                var safeImage = escapeHtml(image);

                return [
                    '<div class="swiper-slide" data-color="', colorName, '" data-detail-main-slide="', index, '">',
                        '<a href="', safeImage, '" target="_blank" class="item" data-pswp-width="770" data-pswp-height="1075">',
                            '<img class="tf-image-zoom lazyloaded" data-zoom="', safeImage, '" data-src="', safeImage, '" src="', safeImage, '" alt="', title, '">',
                        '</a>',
                    '</div>'
                ].join('');
            }).join('');
        }

        function initGallery() {
            if (typeof Swiper === 'undefined') {
                return;
            }

            var thumbsElement = document.querySelector('[data-detail-thumbs-swiper]');
            var mainElement = document.querySelector('[data-detail-main-swiper]');

            if (! thumbsElement || ! mainElement) {
                return;
            }

            cleanSwiperElement(thumbsElement);
            cleanSwiperElement(mainElement);

            thumbsSwiper = new Swiper(thumbsElement, {
                direction: 'vertical',
                slidesPerView: 5,
                spaceBetween: 12,
                watchSlidesProgress: true,
                watchOverflow: true,
                observer: true,
                observeParents: true,
                breakpoints: {
                    0: {
                        direction: 'horizontal',
                        slidesPerView: 4
                    },
                    768: {
                        direction: 'vertical',
                        slidesPerView: 5
                    }
                }
            });

            mainSwiper = new Swiper(mainElement, {
                slidesPerView: 1,
                spaceBetween: 0,
                watchOverflow: true,
                observer: true,
                observeParents: true,
                thumbs: {
                    swiper: thumbsSwiper
                },
                navigation: {
                    nextEl: mainElement.querySelector('.swiper-button-next'),
                    prevEl: mainElement.querySelector('.swiper-button-prev')
                }
            });

            thumbsSwiper.update();
            mainSwiper.update();
            thumbsSwiper.slideTo(0, 0);
            mainSwiper.slideTo(0, 0);
        }

        function addImage(target, value) {
            if (! value) {
                return;
            }

            if (Array.isArray(value)) {
                value.forEach(function (item) {
                    addImage(target, item);
                });
                return;
            }

            if (typeof value === 'object') {
                addImage(target, value.url || value.src || value.image || value.path || value.thumb_url || value.detail_url || value.primary_thumb_url);
                return;
            }

            var image = String(value || '').trim();

            if (image) {
                target.push(image);
            }
        }

        function uniqueImages(images) {
            var seen = {};

            return images.filter(function (image) {
                var key = String(image || '').trim();

                if (! key || seen[key]) {
                    return false;
                }

                seen[key] = true;
                return true;
            });
        }

        function galleryImages(color) {
            var images = [];
            var fallbackImages = [];

            addImage(images, color && (color.image || color.primary_thumb_url));
            addImage(images, color && color.gallery);
            addImage(images, color && color.detail_urls);
            addImage(images, color && color.thumb_urls);
            addImage(images, color && color.images);
            addImage(images, color && color.media);

            addImage(fallbackImages, product.gallery);
            addImage(fallbackImages, product.image);
            addImage(fallbackImages, initialFallbackImage);

            images = uniqueImages(images);

            if (! images.length) {
                images = uniqueImages(fallbackImages);
            }

            return images.length ? images : [initialFallbackImage || ''];
        }

        function refreshLazyImages($scope) {
            var $images = ($scope || $root).find('img');

            $images.each(function () {
                var image = this;
                var src = image.getAttribute('data-src') || image.getAttribute('src');

                if (src) {
                    image.setAttribute('src', src);
                    image.setAttribute('data-src', src);
                }

                image.classList.remove('lazyload');
                image.classList.add('lazyloaded');

                if (window.lazySizes && window.lazySizes.loader && typeof window.lazySizes.loader.unveil === 'function') {
                    window.lazySizes.loader.unveil(image);
                }
            });
        }

        function renderGallery() {
            var color = currentColor();
            var images = galleryImages(color);
            var $thumbsWrapper = $root.find('[data-detail-thumbs]').first();
            var $galleryWrapper = $root.find('[data-detail-gallery]').first();

            if (! $thumbsWrapper.length || ! $galleryWrapper.length) {
                return;
            }

            destroyGallery();
            $thumbsWrapper.html(buildThumbSlidesHtml(images, color));
            $galleryWrapper.html(buildMainSlidesHtml(images, color));
            refreshLazyImages($thumbsWrapper);
            refreshLazyImages($galleryWrapper);

            window.requestAnimationFrame(function () {
                initGallery();

                if (typeof window.initProductDetailPhotoSwipe === 'function') {
                    window.initProductDetailPhotoSwipe();
                }
            });
        }

        function firstAvailableSizeIndex(sizes) {
            for (var index = 0; index < sizes.length; index += 1) {
                if (normalizeSizeLabel(sizes[index]) && ! isSoldOut(sizes[index])) {
                    return index;
                }
            }

            return 0;
        }

        function renderSizes() {
            var sizes = currentSizes();

            selectedSizeIndex = firstAvailableSizeIndex(sizes);

            $('[data-detail-sizes]').html(sizes.map(function (size, index) {
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
                    '<label class="style-text" for="detail-size-js-', index, '" data-value="', escapeHtml(label), '"', soldOut ? ' aria-disabled="true"' : '', '>',
                        '<span class="size-label">', escapeHtml(label), '</span>',
                    '</label>'
                ].join('');
            }).join(''));
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

        function syncPriceAndLabels() {
            var color = currentColor();
            var size = selectedSize();
            var quantity = Math.max(1, Math.min(99, parseInt($('[data-detail-quantity]').val(), 10) || 1));
            var currentPrice = (size && size.price_current) || color.price_current || product.price_current || product.base_price || 0;
            var comparePrice = (size && size.compare_price) || color.compare_price || product.compare_price || 0;
            var currentLabel = (size && size.price_current_label) || color.price_current_label || product.price_current_label || product.price_label || '';
            var compareLabel = (size && size.compare_price_label) || color.compare_price_label || product.compare_price_label || '';
            var currency = currentBaseCurrency();
            var sizeLabel = normalizeSizeLabel(size);

            $('[data-detail-current-price]')
                .text(currentLabel)
                .attr('data-base-price', currentPrice || 0)
                .attr('data-base-currency', currency);

            $('[data-detail-submit-price]')
                .text(formatTotal(currentPrice, quantity) || currentLabel)
                .attr('data-base-price', currentPrice || 0)
                .attr('data-base-currency', currency);

            $('[data-detail-compare-price]')
                .text(compareLabel || '')
                .attr('data-base-price', comparePrice || 0)
                .attr('data-base-currency', currency)
                .toggleClass('d-none', ! compareLabel);

            $('[data-detail-color-label]').text(color.name || '');
            $('[data-detail-color-code]').text(color.color_code || '');
            $('[data-detail-size-label]').text(sizeLabel || '');

            if (window.updateCurrencyConvertedPrices) {
                window.updateCurrencyConvertedPrices();
            }
        }

        function renderSizeChart() {
            var $modal = $('#find_size');
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

            if (! $modal.length) {
                return;
            }

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

        function selectedQuantity() {
            var quantity = parseInt($('[data-detail-quantity]').val(), 10) || 1;

            return Math.max(1, Math.min(99, quantity));
        }

        function submitDetailCart($form) {
            var url = String($form.data('cart-url') || '');
            var color = currentColor();
            var $selectedSize = $('[data-detail-sizes] input[name="detail_size"]:checked').first();
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

            $('[data-detail-cart-submit]').prop('disabled', true);

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
                $('[data-detail-cart-submit]').prop('disabled', false);
            });
        }

        $(document).off('click.productDetailThumbs').on('click.productDetailThumbs', '[data-detail-thumb-slide]', function (event) {
            var index = Number($(this).attr('data-detail-thumb-slide') || 0);

            event.preventDefault();

            if (mainSwiper && typeof mainSwiper.slideTo === 'function') {
                mainSwiper.slideTo(index);
            }

            if (thumbsSwiper && typeof thumbsSwiper.slideTo === 'function') {
                thumbsSwiper.slideTo(index);
            }
        });

        $(document).on('change', '[data-detail-colors] input[name="detail_color"]', function () {
            selectedColorIndex = Number($(this).data('color-index') || 0);
            selectedSizeIndex = 0;
            syncColorInputs();
            updateSelectedColorUrl();
            renderGallery();
            renderSizes();
            syncPriceAndLabels();
        });

        $(document).on('change', '[data-detail-sizes] input[name="detail_size"]', function () {
            selectedSizeIndex = Number($(this).data('size-index') || 0);
            syncPriceAndLabels();
        });

        $(document).on('click', '[data-detail-qty]', function () {
            var $quantity = $('[data-detail-quantity]');
            var current = parseInt($quantity.val(), 10) || 1;
            var next = $(this).data('detail-qty') === 'decrease'
                ? Math.max(1, current - 1)
                : Math.min(99, current + 1);

            $quantity.val(next);
            syncPriceAndLabels();
        });

        $(document).on('change keyup', '[data-detail-quantity]', function () {
            $(this).val(selectedQuantity());
            syncPriceAndLabels();
        });

        $(document).on('click', '[data-detail-find-size]', function (event) {
            event.preventDefault();
            renderSizeChart();
            showModal('#find_size');
        });

        $(document).on('submit', '[data-detail-cart-form]', function (event) {
            event.preventDefault();
            submitDetailCart($(this));
        });

        selectedColorIndex = initialColorIndexFromUrl();
        syncColorInputs();
        renderSizeChart();
        renderGallery();
        renderSizes();
        syncPriceAndLabels();
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
