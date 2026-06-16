(function ($) {
    'use strict';

    var $page = $('[data-cart-page]').first();
    var quantityTimers = {};

    if (!$page.length) {
        return;
    }

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function clampQuantity(value) {
        value = parseInt(value, 10);

        if (Number.isNaN(value)) {
            return 1;
        }

        return Math.max(1, Math.min(99, value));
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

    function cartItem($element) {
        return $element.closest('[data-cart-page-item], #shoppingCart [data-cart-key]');
    }

    function quantityInput($item) {
        var $input = $item.find('[data-cart-page-quantity]').first();

        if (!$input.length) {
            $input = $item.find('input[name="number"]').first();
        }

        return $input;
    }

    function updateUrl($item) {
        return $item.attr('data-cart-update-url') || '';
    }

    function removeUrl($item) {
        return $item.attr('data-cart-remove-url') || '';
    }

    function setLoading($item, loading) {
        $item.toggleClass('is-loading', loading);
        $item.attr('aria-busy', loading ? 'true' : 'false');
        $item.find('button, input').prop('disabled', loading);
    }

    function currentTermsState() {
        return $('[data-cart-terms]').is(':checked');
    }

    function syncCheckoutState() {
        var $terms = $('[data-cart-terms]').first();
        var $checkout = $('[data-cart-checkout]').first();
        var checked = $terms.is(':checked');

        $checkout.toggleClass('is-disabled', !checked).attr('aria-disabled', checked ? 'false' : 'true');

        if (checked) {
            $('[data-cart-terms-error]').addClass('d-none');
        }
    }

    function replaceCartPage(response, termsChecked) {
        if (!response.cart_page_html) {
            return;
        }

        var $newContent = $('<div>').html(response.cart_page_html).find('[data-cart-page-content]').first();
        var $currentContent = $('[data-cart-page-content]').first();

        if (!$newContent.length || !$currentContent.length) {
            return;
        }

        $currentContent.replaceWith($newContent);

        if (termsChecked) {
            $('[data-cart-terms]').prop('checked', true);
        }

        syncCheckoutState();
    }

    function replaceCartDrawer(response) {
        if (!response.cart_html) {
            return;
        }

        var $fragment = $('<div>').html(response.cart_html);
        var $newModal = $fragment.find('#shoppingCart');
        var $modal = $('#shoppingCart');

        if (!$newModal.length || !$modal.length) {
            return;
        }

        $modal.find('[data-cart-items]').html($newModal.find('[data-cart-items]').html());

        var $newSubtotal = $newModal.find('[data-cart-subtotal]');
        var $subtotal = $modal.find('[data-cart-subtotal]');

        if ($newSubtotal.length && $subtotal.length) {
            $subtotal.text($newSubtotal.text());
            $subtotal.attr('data-base-price', $newSubtotal.attr('data-base-price') || 0);
            $subtotal.attr('data-base-currency', $newSubtotal.attr('data-base-currency') || '');
        }
    }

    function syncCart(response) {
        if (!response || !response.ok) {
            return;
        }

        $('[data-cart-page-error]').addClass('d-none');

        var termsChecked = currentTermsState();
        var count = response.cart_state && response.cart_state.count ? response.cart_state.count : 0;

        $('[data-cart-count]').text(count);
        replaceCartPage(response, termsChecked);
        replaceCartDrawer(response);

        if (window.updateCurrencyConvertedPrices) {
            window.updateCurrencyConvertedPrices();
        }

        $(document).trigger('front:cart-updated', [response.cart_state || {}]);
    }

    function showError() {
        var $error = $('[data-cart-page-error]').first();

        if ($error.length) {
            $error.text($page.attr('data-cart-error-message') || 'Unable to update cart.').removeClass('d-none');
            $error.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        window.alert($page.attr('data-cart-error-message') || 'Unable to update cart.');
    }

    function submitQuantity($item, quantity) {
        var url = updateUrl($item);

        if (!url || $item.data('cartRequestPending')) {
            return;
        }

        quantity = clampQuantity(quantity);
        quantityInput($item).val(quantity);
        $item.data('cartRequestPending', true);
        setLoading($item, true);

        requestCart(url, 'PATCH', { quantity: quantity })
            .done(syncCart)
            .fail(showError)
            .always(function () {
                $item.data('cartRequestPending', false);
                setLoading($item, false);
            });
    }

    function scheduleQuantityUpdate($item, quantity, delay) {
        var key = String($item.attr('data-cart-key') || 'cart-item');

        window.clearTimeout(quantityTimers[key]);
        quantityTimers[key] = window.setTimeout(function () {
            submitQuantity($item, quantity);
        }, delay || 250);
    }

    function submitRemove($item) {
        var url = removeUrl($item);

        if (!url || $item.data('cartRequestPending')) {
            return;
        }

        $item.data('cartRequestPending', true);
        setLoading($item, true);

        requestCart(url, 'DELETE')
            .done(syncCart)
            .fail(showError)
            .always(function () {
                $item.data('cartRequestPending', false);
                setLoading($item, false);
            });
    }

    $(document).on('click', '[data-cart-page-qty], #shoppingCart [data-cart-qty]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $item = cartItem($button);
        var $input = quantityInput($item);
        var before = clampQuantity($input.val());
        var delta = String($button.attr('data-cart-page-qty') || $button.attr('data-cart-qty')) === 'decrease' ? -1 : 1;

        window.setTimeout(function () {
            var after = clampQuantity($input.val());

            if (after === before) {
                after = clampQuantity(before + delta);
                $input.val(after);
            }

            submitQuantity($item, after);
        }, 0);
    });

    $(document).on('input', '[data-cart-page-quantity]', function () {
        var $input = $(this);
        scheduleQuantityUpdate(cartItem($input), clampQuantity($input.val()), 350);
    });

    $(document).on('change blur', '[data-cart-page-quantity]', function () {
        var $input = $(this);
        var quantity = clampQuantity($input.val());

        $input.val(quantity);
        scheduleQuantityUpdate(cartItem($input), quantity, 50);
    });

    $(document).on('keydown', '[data-cart-page-quantity]', function (event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        var $input = $(this);
        var quantity = clampQuantity($input.val());

        $input.val(quantity);
        submitQuantity(cartItem($input), quantity);
    });

    $(document).on('click', '[data-cart-page-remove], #shoppingCart [data-cart-remove]', function (event) {
        event.preventDefault();
        submitRemove(cartItem($(this)));
    });

    $(document).on('change', '[data-cart-terms]', syncCheckoutState);

    $(document).on('click', '[data-cart-checkout]', function (event) {
        if ($('[data-cart-terms]').is(':checked')) {
            return;
        }

        event.preventDefault();
        $('[data-cart-terms-error]').removeClass('d-none');
        $('[data-cart-terms]').trigger('focus');
    });

    syncCheckoutState();
})(jQuery);
