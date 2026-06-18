(() => {
    'use strict';

    const page = document.querySelector('[data-checkout-page]');

    if (! page) {
        return;
    }

    const currency = page.dataset.checkoutCurrency || 'SYP';
    const locale = page.dataset.checkoutLocale || document.documentElement.lang || 'ar';
    const couponPreviewUrl = page.dataset.checkoutCouponPreviewUrl || '';
    const couponErrorMessage = page.dataset.checkoutCouponErrorMessage || '';
    const shippingOutput = page.querySelector('[data-checkout-shipping-cost]');
    const totalOutput = page.querySelector('[data-checkout-total]');
    const discountRow = page.querySelector('[data-checkout-coupon-row]');
    const discountOutput = page.querySelector('[data-checkout-coupon-discount]');
    const couponInput = page.querySelector('[data-checkout-coupon-input]');
    const couponApplyButton = page.querySelector('[data-checkout-coupon-apply]');
    const couponApplyLabel = page.querySelector('[data-checkout-coupon-apply-label]');
    const couponApplyingLabel = page.querySelector('[data-checkout-coupon-applying-label]');
    const couponRemoveButton = page.querySelector('[data-checkout-coupon-remove]');
    const couponFeedback = page.querySelector('[data-checkout-coupon-feedback]');
    const form = page.querySelector('[data-checkout-form]');
    const submitButton = page.querySelector('[data-checkout-submit]');
    const submitLabel = page.querySelector('[data-checkout-submit-label]');
    const submittingLabel = page.querySelector('[data-checkout-submitting-label]');
    const subtotal = Number(totalOutput?.dataset.checkoutSubtotal || 0);
    let couponDiscount = 0;
    let appliedCouponCode = '';
    let couponRequestInFlight = false;

    const formatAmount = (value) => {
        const amount = Number.isFinite(value) ? value : 0;

        return `${new Intl.NumberFormat(locale, {
            maximumFractionDigits: 0,
        }).format(amount)} ${currency}`;
    };

    const normalizedCouponCode = () => (couponInput?.value || '').trim().toUpperCase();

    const refreshSummary = () => {
        const selected = page.querySelector('[data-shipping-method]:checked');
        const shippingCost = Number(selected?.dataset.shippingCost || 0);
        const total = Math.max(0, subtotal - couponDiscount + shippingCost);

        if (shippingOutput) {
            shippingOutput.dataset.basePrice = String(shippingCost);
            shippingOutput.textContent = formatAmount(shippingCost);
        }

        if (discountOutput) {
            discountOutput.textContent = `- ${formatAmount(couponDiscount)}`;
        }

        if (totalOutput) {
            totalOutput.dataset.basePrice = String(total);
            totalOutput.textContent = formatAmount(total);
        }
    };

    const setCouponFeedback = (message = '', type = '') => {
        if (! couponFeedback) {
            return;
        }

        couponFeedback.textContent = message;
        couponFeedback.classList.remove('text-success', 'text-danger');

        if (type === 'success') {
            couponFeedback.classList.add('text-success');
        } else if (type === 'error') {
            couponFeedback.classList.add('text-danger');
        }
    };

    const setCouponLoading = (loading) => {
        couponRequestInFlight = loading;

        if (couponApplyButton) {
            couponApplyButton.disabled = loading;
            couponApplyButton.setAttribute('aria-busy', loading ? 'true' : 'false');
        }

        if (couponInput) {
            couponInput.readOnly = loading;
        }

        couponApplyLabel?.classList.toggle('d-none', loading);
        couponApplyingLabel?.classList.toggle('d-none', ! loading);
    };

    const clearAppliedCoupon = (clearInput = false) => {
        couponDiscount = 0;
        appliedCouponCode = '';
        discountRow?.classList.add('d-none');
        couponRemoveButton?.classList.add('d-none');

        if (clearInput && couponInput) {
            couponInput.value = '';
        }

        refreshSummary();
    };

    const firstErrorMessage = (payload) => {
        const errors = payload?.errors || {};
        const firstMessages = Object.values(errors)[0];

        if (Array.isArray(firstMessages) && firstMessages[0]) {
            return firstMessages[0];
        }

        return payload?.message || couponErrorMessage;
    };

    const previewCoupon = async () => {
        const code = normalizedCouponCode();

        if (! couponInput || ! couponPreviewUrl || couponRequestInFlight) {
            return false;
        }

        if (! code) {
            clearAppliedCoupon(false);
            setCouponFeedback(couponInput.dataset.requiredMessage || couponErrorMessage, 'error');
            couponInput.focus();

            return false;
        }

        clearAppliedCoupon(false);
        setCouponFeedback('');
        setCouponLoading(true);

        try {
            const body = new FormData();
            body.append('coupon_code', code);

            const csrfToken = form?.querySelector('input[name="_token"]')?.value;

            if (csrfToken) {
                body.append('_token', csrfToken);
            }

            const response = await fetch(couponPreviewUrl, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            let payload = {};

            try {
                payload = await response.json();
            } catch (error) {
                payload = {};
            }

            if (! response.ok || ! payload.ok) {
                setCouponFeedback(firstErrorMessage(payload), 'error');

                return false;
            }

            couponDiscount = Math.max(0, Number(payload.discount_amount || 0));
            appliedCouponCode = String(payload.code || code).trim().toUpperCase();
            couponInput.value = appliedCouponCode;
            discountRow?.classList.remove('d-none');
            couponRemoveButton?.classList.remove('d-none');
            setCouponFeedback(payload.message || '', 'success');
            refreshSummary();

            return true;
        } catch (error) {
            setCouponFeedback(couponErrorMessage, 'error');

            return false;
        } finally {
            setCouponLoading(false);
        }
    };

    page.addEventListener('change', (event) => {
        if (event.target.matches('[data-shipping-method]')) {
            refreshSummary();
        }
    });

    couponInput?.addEventListener('input', () => {
        if (normalizedCouponCode() !== appliedCouponCode) {
            clearAppliedCoupon(false);
            setCouponFeedback('');
        }
    });

    couponApplyButton?.addEventListener('click', () => {
        void previewCoupon();
    });

    couponInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            void previewCoupon();
        }
    });

    couponRemoveButton?.addEventListener('click', () => {
        clearAppliedCoupon(true);
        setCouponFeedback('');
        couponInput?.focus();
    });

    form?.addEventListener('submit', async (event) => {
        if (! form.checkValidity()) {
            return;
        }

        const couponCode = normalizedCouponCode();

        if (couponCode && couponPreviewUrl && couponCode !== appliedCouponCode) {
            event.preventDefault();

            if (await previewCoupon()) {
                if (submitButton) {
                    form.requestSubmit(submitButton);
                } else {
                    form.requestSubmit();
                }
            }

            return;
        }

        form.classList.add('is-submitting');

        if (submitButton) {
            submitButton.disabled = true;
        }

        submitLabel?.classList.add('d-none');
        submittingLabel?.classList.remove('d-none');
    });

    refreshSummary();
})();
