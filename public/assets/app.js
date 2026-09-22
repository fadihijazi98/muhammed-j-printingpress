(function () {
    'use strict';

    /* Live total for any form that has a quantity field and a price field. */
    function wireTotals() {
        document.querySelectorAll('[data-total-form]').forEach(function (form) {
            /* Scoped to inputs: <option> tags carry data-price too, as the
               saved price a select fills in. */
            var quantity = form.querySelector('input[data-quantity]');
            var price    = form.querySelector('input[data-price]');
            var output   = form.querySelector('[data-total]');

            if (!quantity || !price || !output) {
                return;
            }

            function toNumber(value) {
                var arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫'];
                var western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'];

                var text = String(value || '');

                arabic.forEach(function (digit, index) {
                    text = text.split(digit).join(western[index]);
                });

                var parsed = parseFloat(text.replace(/,/g, '').trim());

                return isNaN(parsed) ? 0 : parsed;
            }

            function recalculate() {
                var total = toNumber(quantity.value) * toNumber(price.value);

                output.textContent = '₪ ' + total.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            quantity.addEventListener('input', recalculate);
            price.addEventListener('input', recalculate);

            recalculate();
        });
    }

    /* Choosing a job type / material / worker fills in its saved price. */
    function wireDefaults() {
        document.querySelectorAll('[data-fills-price]').forEach(function (select) {
            var target = document.querySelector(select.getAttribute('data-fills-price'));

            if (!target) {
                return;
            }

            function showUnit() {
                var option = select.options[select.selectedIndex];
                var unit   = option ? option.getAttribute('data-unit') : '';

                document.querySelectorAll('[data-unit-for="' + select.id + '"]').forEach(function (label) {
                    label.textContent = unit || '';
                });
            }

            function applyDefault() {
                var option = select.options[select.selectedIndex];
                var value  = option ? option.getAttribute('data-price') : null;

                if (value !== null && value !== '') {
                    target.value = value;
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                }

                showUnit();
            }

            select.addEventListener('change', applyDefault);

            /* On load, never overwrite a price the user already typed or is editing. */
            showUnit();

            if (!target.value) {
                applyDefault();
            }
        });
    }

    function wireConfirmations() {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });
    }

    function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js').catch(function () {
                /* The app works fine without it; only the install prompt is lost. */
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        wireDefaults();
        wireTotals();
        wireConfirmations();
        registerServiceWorker();
    });
}());
