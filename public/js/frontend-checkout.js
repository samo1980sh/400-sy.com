(() => {
    'use strict';

    const page = document.querySelector('[data-checkout-page]');

    if (!page) {
        return;
    }

    const currency = page.dataset.checkoutCurrency || 'SYP';
    const locale = page.dataset.checkoutLocale || document.documentElement.lang || 'ar';
    const shippingOutput = page.querySelector('[data-checkout-shipping-cost]');
    const totalOutput = page.querySelector('[data-checkout-total]');
    const subtotal = Number(totalOutput?.dataset.checkoutSubtotal || 0);
    const form = page.querySelector('[data-checkout-form]');
    const submitButton = page.querySelector('[data-checkout-submit]');
    const submitLabel = page.querySelector('[data-checkout-submit-label]');
    const submittingLabel = page.querySelector('[data-checkout-submitting-label]');

    const formatAmount = (value) => {
        const amount = Number.isFinite(value) ? value : 0;
        return `${new Intl.NumberFormat(locale, { maximumFractionDigits: 0 }).format(amount)} ${currency}`;
    };

    const refreshSummary = () => {
        const selected = page.querySelector('[data-shipping-method]:checked');
        const shippingCost = Number(selected?.dataset.shippingCost || 0);
        const total = subtotal + shippingCost;

        if (shippingOutput) {
            shippingOutput.dataset.basePrice = String(shippingCost);
            shippingOutput.textContent = formatAmount(shippingCost);
        }

        if (totalOutput) {
            totalOutput.dataset.basePrice = String(total);
            totalOutput.textContent = formatAmount(total);
        }
    };

    page.addEventListener('change', (event) => {
        if (event.target.matches('[data-shipping-method]')) {
            refreshSummary();
        }
    });

    form?.addEventListener('submit', () => {
        if (!form.checkValidity()) {
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
