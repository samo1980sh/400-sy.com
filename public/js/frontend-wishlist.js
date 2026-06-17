(function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function toArray(value) {
        return Array.prototype.slice.call(value || []);
    }

    function getProductFromButton(button) {
        var card = button.closest('.card-product');

        if (card && card.dataset.product) {
            try {
                return JSON.parse(card.dataset.product || '{}');
            } catch (error) {
                return {};
            }
        }

        if (window.jQuery) {
            var modal = window.jQuery(button).closest('#quick_view, #quick_add');
            if (modal.length) {
                return modal.data('product') || {};
            }
        }

        return {};
    }

    function readButtonState(button) {
        var product = getProductFromButton(button);
        var addUrl = button.dataset.wishlistAddUrl || product.wishlist_add_url || '';
        var removeUrl = button.dataset.wishlistRemoveUrl || product.wishlist_remove_url || '';
        var slug = button.dataset.productSlug || product.slug || '';
        var isActive = button.classList.contains('active') || button.getAttribute('aria-pressed') === 'true' || !!product.is_in_wishlist;

        return {
            product: product,
            slug: String(slug || ''),
            addUrl: String(addUrl || ''),
            removeUrl: String(removeUrl || ''),
            isActive: isActive
        };
    }

    function wishlistLabel(button, active) {
        var addLabel = button.dataset.wishlistAddLabel || '';
        var removeLabel = button.dataset.wishlistRemoveLabel || '';

        return active ? (removeLabel || addLabel) : (addLabel || removeLabel);
    }

    function setButtonState(button, active) {
        var label = wishlistLabel(button, active);

        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');

        if (label) {
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);

            toArray(button.querySelectorAll('[data-wishlist-label], .tooltip')).forEach(function (node) {
                node.textContent = label;
            });
        }
    }

    function syncProductButtons(slug, active) {
        if (!slug) {
            return;
        }

        toArray(document.querySelectorAll('[data-wishlist-button]')).forEach(function (button) {
            var state = readButtonState(button);
            if (state.slug === slug || (state.product && state.product.slug === slug)) {
                setButtonState(button, active);
                if (state.product) {
                    state.product.is_in_wishlist = active;
                }
            }
        });
    }

    function updateCounters(count) {
        toArray(document.querySelectorAll('[data-wishlist-count]')).forEach(function (node) {
            node.textContent = String(count || 0);
        });
    }

    function refreshEmptyState() {
        var grid = document.querySelector('[data-wishlist-grid]');
        var empty = document.querySelector('[data-wishlist-empty]');

        if (!grid || !empty) {
            return;
        }

        var hasItems = !!grid.querySelector('.card-product');
        empty.classList.toggle('d-none', hasItems);
        grid.classList.toggle('d-none', !hasItems);
    }

    function removeWishlistCard(slug) {
        if (!slug) {
            return;
        }

        toArray(document.querySelectorAll('[data-wishlist-grid] .card-product')).forEach(function (card) {
            try {
                var product = JSON.parse(card.dataset.product || '{}');
                if (product.slug === slug) {
                    card.remove();
                }
            } catch (error) {
                // Ignore malformed product data.
            }
        });

        refreshEmptyState();
    }


    function syncModalButtons(modal) {
        if (!modal || !window.jQuery) {
            return;
        }

        var product = window.jQuery(modal).data('product') || {};

        toArray(modal.querySelectorAll('[data-wishlist-button]')).forEach(function (button) {
            if (product.slug) {
                button.dataset.productSlug = product.slug;
            }
            if (product.wishlist_add_url) {
                button.dataset.wishlistAddUrl = product.wishlist_add_url;
            }
            if (product.wishlist_remove_url) {
                button.dataset.wishlistRemoveUrl = product.wishlist_remove_url;
            }
            setButtonState(button, !!product.is_in_wishlist);
        });
    }

    function requestWishlist(url, method) {
        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken()
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Wishlist request failed.');
            }

            return response.json();
        });
    }

    document.addEventListener('shown.bs.modal', function (event) {
        if (event.target && (event.target.id === 'quick_view' || event.target.id === 'quick_add')) {
            syncModalButtons(event.target);
        }
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-wishlist-button]');

        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (button.dataset.wishlistLoading === '1') {
            return;
        }

        var state = readButtonState(button);
        var url = state.isActive ? state.removeUrl : state.addUrl;
        var method = state.isActive ? 'DELETE' : 'POST';

        if (!url) {
            return;
        }

        button.dataset.wishlistLoading = '1';
        button.classList.add('loading');

        requestWishlist(url, method)
            .then(function (payload) {
                var active = !!payload.in_wishlist;
                var slug = payload.product_slug || state.slug;
                var count = payload.wishlist_count || (payload.wishlist_state && payload.wishlist_state.count) || 0;

                syncProductButtons(slug, active);
                updateCounters(count);

                if (!active) {
                    removeWishlistCard(slug);
                }
            })
            .catch(function () {
                setButtonState(button, state.isActive);
            })
            .finally(function () {
                button.dataset.wishlistLoading = '0';
                button.classList.remove('loading');
            });
    }, true);
})();
