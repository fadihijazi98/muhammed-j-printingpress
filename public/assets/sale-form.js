(function () {
    'use strict';

    var ARABIC  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫'];
    var WESTERN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'];

    function toNumber(value) {
        var text = String(value || '');

        ARABIC.forEach(function (digit, index) {
            text = text.split(digit).join(WESTERN[index]);
        });

        var parsed = parseFloat(text.replace(/,/g, '').trim());

        return isNaN(parsed) ? 0 : parsed;
    }

    function money(amount) {
        return '₪ ' + amount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function plain(amount) {
        return amount.toFixed(2);
    }

    /*
     * Every "total" field is filled in for the user but stays editable. Once it
     * is edited by hand it is left alone — that is how an agreed price or a
     * supplier discount survives — until "إعادة الحساب" hands it back.
     */
    function isAuto(field) {
        return field.getAttribute('data-touched') !== '1';
    }

    function markTouched(field) {
        field.setAttribute('data-touched', '1');
    }

    function computedFor(group) {
        var quantity = group.querySelector('input[data-quantity]');
        var price    = group.querySelector('input[data-price]');

        if (!quantity || !price) {
            return 0;
        }

        return toNumber(quantity.value) * toNumber(price.value);
    }

    function refreshGroup(group) {
        var computed = computedFor(group);
        var display  = group.querySelector('[data-computed]');
        var total    = group.querySelector('input[data-final]');

        if (display) {
            display.textContent = money(computed);
        }

        if (total && isAuto(total)) {
            total.value = computed > 0 ? plain(computed) : '';
        }

        refreshSummary();
    }

    function sumLines(selector) {
        var total = 0;

        document.querySelectorAll(selector + ' input[data-final]').forEach(function (field) {
            total += toNumber(field.value);
        });

        return total;
    }

    function refreshSummary() {
        var saleField = document.querySelector('input[name="total"]');
        var sale      = saleField ? toNumber(saleField.value) : 0;

        var materials = sumLines('[data-lines="materials"]');
        var workers   = sumLines('[data-lines="workers"]');
        var costs     = materials + workers;

        var set = function (name, amount) {
            var node = document.querySelector('[data-summary="' + name + '"]');

            if (node) {
                node.textContent = money(amount);
            }
        };

        set('sale', sale);
        set('materials', materials);
        set('workers', workers);
        set('costs', costs);
        set('net', sale - costs);

        var net = document.querySelector('[data-summary="net"]');

        if (net) {
            net.classList.toggle('negative', sale - costs < 0);
        }
    }

    function wireGroup(group) {
        group.querySelectorAll('input[data-quantity], input[data-price]').forEach(function (field) {
            field.addEventListener('input', function () {
                refreshGroup(group);
            });
        });

        var total = group.querySelector('input[data-final]');

        if (total) {
            total.addEventListener('input', function () {
                markTouched(total);
                refreshSummary();
            });
        }

        var select = group.querySelector('[data-line-select]');

        if (select) {
            select.addEventListener('change', function () {
                var option = select.options[select.selectedIndex];
                var price  = group.querySelector('input[data-price]');
                var unit   = group.querySelector('.line-unit');

                if (price && option && option.getAttribute('data-price')) {
                    price.value = option.getAttribute('data-price');
                }

                if (unit) {
                    unit.textContent = option ? '(' + (option.getAttribute('data-unit') || '') + ')' : '';
                }

                refreshGroup(group);
            });
        }
    }

    function addLine(kind, values) {
        var container = document.querySelector('[data-lines="' + kind + '"]');
        var template  = document.getElementById('tpl-' + kind);

        if (!container || !template) {
            return null;
        }

        var index = container.children.length;
        var html  = template.innerHTML.split('__i__').join(String(index));

        var holder = document.createElement('div');
        holder.innerHTML = html.trim();

        var line = holder.firstElementChild;
        container.appendChild(line);

        if (values) {
            Object.keys(values).forEach(function (key) {
                var field = line.querySelector('[name$="[' + key + ']"]');

                if (field) {
                    field.value = values[key];
                }
            });

            var total = line.querySelector('input[data-final]');

            /* A saved line keeps the total exactly as it was stored. */
            if (total && total.value !== '') {
                markTouched(total);
            }
        }

        wireGroup(line);

        var select = line.querySelector('[data-line-select]');
        var unit   = line.querySelector('.line-unit');

        if (select && unit) {
            var option = select.options[select.selectedIndex];
            unit.textContent = option && option.value ? '(' + (option.getAttribute('data-unit') || '') + ')' : '';
        }

        line.querySelector('[data-remove]').addEventListener('click', function () {
            line.remove();
            reindex(kind);
            refreshSummary();
        });

        refreshSummary();

        return line;
    }

    /* Names must stay a gap-free 0,1,2… list after a middle line is removed. */
    function reindex(kind) {
        var container = document.querySelector('[data-lines="' + kind + '"]');

        if (!container) {
            return;
        }

        Array.prototype.forEach.call(container.children, function (line, index) {
            line.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/\[\d+\]/, '[' + index + ']');
            });
        });
    }

    function readJson(id) {
        var node = document.getElementById(id);

        if (!node) {
            return null;
        }

        try {
            return JSON.parse(node.textContent);
        } catch (error) {
            return null;
        }
    }

    /* Puts a rejected line's message back under the exact field that caused it. */
    function showLineErrors(errors) {
        if (!errors) {
            return;
        }

        Object.keys(errors).forEach(function (key) {
            var parts = key.split('.');

            if (parts.length !== 3) {
                return;
            }

            var container = document.querySelector('[data-lines="' + parts[0] + '"]');

            if (!container) {
                return;
            }

            var line = container.children[Number(parts[1])];

            if (!line) {
                return;
            }

            var field = line.querySelector('[name$="[' + parts[2] + ']"]');

            if (!field) {
                return;
            }

            field.classList.add('error');

            var message = document.createElement('div');
            message.className = 'msg';
            message.textContent = errors[key];

            field.parentNode.appendChild(message);
        });
    }

    function init() {
        var saleGroup = document.querySelector('[data-calc="sale"]');
        var finalSale = document.querySelector('input[name="total"]');

        if (finalSale && finalSale.value !== '') {
            markTouched(finalSale);
        }

        if (saleGroup && finalSale) {
            saleGroup.querySelectorAll('input[data-quantity], input[data-price]').forEach(function (field) {
                field.addEventListener('input', function () {
                    var computed = computedFor(saleGroup);
                    var display  = saleGroup.querySelector('[data-computed]');

                    if (display) {
                        display.textContent = money(computed);
                    }

                    if (isAuto(finalSale)) {
                        finalSale.value = computed > 0 ? plain(computed) : '';
                    }

                    refreshSummary();
                });
            });

            finalSale.addEventListener('input', function () {
                markTouched(finalSale);
                refreshSummary();
            });

            var reset = document.querySelector('[data-reset-final]');

            if (reset) {
                reset.addEventListener('click', function () {
                    finalSale.removeAttribute('data-touched');

                    var computed = computedFor(saleGroup);
                    finalSale.value = computed > 0 ? plain(computed) : '';

                    refreshSummary();
                });
            }

            var jobType = document.getElementById('job_type_id');

            if (jobType) {
                jobType.addEventListener('change', function () {
                    var computed = computedFor(saleGroup);
                    var display  = saleGroup.querySelector('[data-computed]');

                    if (display) {
                        display.textContent = money(computed);
                    }

                    if (isAuto(finalSale)) {
                        finalSale.value = computed > 0 ? plain(computed) : '';
                    }

                    refreshSummary();
                });
            }

            var computedNow = computedFor(saleGroup);
            var displayNow  = saleGroup.querySelector('[data-computed]');

            if (displayNow) {
                displayNow.textContent = money(computedNow);
            }
        }

        document.querySelectorAll('[data-add]').forEach(function (button) {
            button.addEventListener('click', function () {
                var line = addLine(button.getAttribute('data-add'), null);

                if (line) {
                    var select = line.querySelector('select');

                    if (select) {
                        select.focus();
                    }
                }
            });
        });

        var existing = readJson('existing-lines');

        if (existing) {
            (existing.materials || []).forEach(function (row) {
                addLine('materials', row);
            });

            (existing.workers || []).forEach(function (row) {
                addLine('workers', row);
            });
        }

        showLineErrors(readJson('line-errors'));
        refreshSummary();
    }

    document.addEventListener('DOMContentLoaded', init);
}());
