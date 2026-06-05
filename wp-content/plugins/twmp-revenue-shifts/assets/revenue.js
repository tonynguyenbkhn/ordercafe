(function () {
    function digits(value) {
        var cleaned = String(value || '').replace(/[^\d-]/g, '');
        var parsed = parseInt(cleaned, 10);

        return Number.isFinite(parsed) ? parsed : 0;
    }

    function money(value) {
        return String(Math.trunc(value || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function setStatus(root, message, isError) {
        var status = root.querySelector('[data-revenue-status]');

        if (!status) {
            return;
        }

        status.textContent = message || '';
        status.classList.toggle('is-error', !!isError);
    }

    function setButtonLoading(button, isLoading, loadingText) {
        if (!button) {
            return;
        }

        if (!button.hasAttribute('data-revenue-original-text')) {
            button.setAttribute('data-revenue-original-text', button.textContent.trim());
        }

        button.disabled = !!isLoading;
        button.classList.toggle('is-loading', !!isLoading);

        if (isLoading && loadingText) {
            button.textContent = loadingText;
        } else {
            button.textContent = button.getAttribute('data-revenue-original-text') || button.textContent;
        }
    }

    function getEntry(root, date, shift) {
        var fields = {};
        var selector = '[data-revenue-input][data-date="' + date + '"][data-shift="' + shift + '"]';

        root.querySelectorAll(selector).forEach(function (input) {
            var field = input.getAttribute('data-field');

            if (!field) {
                return;
            }

            fields[field] = input.hasAttribute('data-revenue-money') ? digits(input.value) : input.value;
        });

        return fields;
    }

    function calculate(entry) {
        var cashActual = digits(entry.cash_sales) - digits(entry.exchange_cash_out) - digits(entry.expenses_cash);
        var bankActual = digits(entry.bank_transfer_sales) + digits(entry.exchange_cash_out);

        return {
            cash_actual: cashActual,
            bank_transfer_actual: bankActual,
            revenue_actual: cashActual + bankActual
        };
    }

    function updateTotals(root, date, shift) {
        var totals = calculate(getEntry(root, date, shift));

        Object.keys(totals).forEach(function (field) {
            var output = root.querySelector('[data-revenue-total][data-date="' + date + '"][data-shift="' + shift + '"][data-field="' + field + '"]');

            if (output) {
                output.textContent = money(totals[field]);
            }
        });
    }

    function collectEntries(root) {
        var entries = {};

        root.querySelectorAll('[data-revenue-input]').forEach(function (input) {
            var date = input.getAttribute('data-date');
            var shift = input.getAttribute('data-shift');
            var field = input.getAttribute('data-field');

            if (!date || !shift || !field) {
                return;
            }

            if (!entries[date]) {
                entries[date] = {};
            }

            if (!entries[date][shift]) {
                entries[date][shift] = {};
            }

            entries[date][shift][field] = input.hasAttribute('data-revenue-money') ? digits(input.value) : input.value;
        });

        return entries;
    }

    function setMoneyInput(root, date, shift, field, value) {
        var input = null;

        root.querySelectorAll('[data-revenue-input][data-revenue-money]').forEach(function (candidate) {
            if (input) {
                return;
            }

            if (
                candidate.getAttribute('data-date') === date &&
                candidate.getAttribute('data-shift') === shift &&
                candidate.getAttribute('data-field') === field
            ) {
                input = candidate;
            }
        });

        if (!input) {
            return;
        }

        input.value = value ? money(value) : '';
        updateTotals(root, date, shift);
    }

    function applyImportedEntries(root, entries) {
        var applied = 0;

        root.querySelectorAll('[data-revenue-input][data-revenue-money][data-field="cash_sales"], [data-revenue-input][data-revenue-money][data-field="bank_transfer_sales"]').forEach(function (input) {
            input.value = '';
            updateTotals(root, input.getAttribute('data-date'), input.getAttribute('data-shift'));
        });

        Object.keys(entries || {}).forEach(function (date) {
            Object.keys(entries[date] || {}).forEach(function (shift) {
                var entry = entries[date][shift] || {};

                setMoneyInput(root, date, shift, 'cash_sales', digits(entry.cash_sales));
                setMoneyInput(root, date, shift, 'bank_transfer_sales', digits(entry.bank_transfer_sales));
                applied += 1;
            });
        });

        return applied;
    }

    function bindImport(root) {
        var button = root.querySelector('[data-revenue-import]');

        if (!button) {
            return;
        }

        button.addEventListener('click', function () {
            var config = window.twmpRevenueShifts || {};
            var body = new FormData();

            setButtonLoading(button, true, config.i18n && config.i18n.importing ? config.i18n.importing : 'Đang lấy...');
            setStatus(root, config.i18n && config.i18n.importing ? config.i18n.importing : 'Đang lấy từ đơn hàng...', false);
            body.append('action', 'twmp_revenue_import_orders');
            body.append('nonce', config.nonce || '');
            body.append('branch_id', root.querySelector('[data-revenue-branch]').value || '');
            body.append('month', root.querySelector('[data-revenue-month]').value || '');

            fetch(config.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: body
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (!payload || !payload.success) {
                        throw new Error(payload && payload.data && payload.data.message ? payload.data.message : '');
                    }

                    var applied = applyImportedEntries(root, payload.data.entries || {});
                    var message = payload.data && payload.data.message ? payload.data.message : 'Đã lấy từ đơn hàng.';

                    if (!applied) {
                        message += ' Không có ca nào khớp với bảng đang mở.';
                    }

                    setStatus(root, message, false);
                })
                .catch(function (error) {
                    setStatus(root, error.message || (config.i18n && config.i18n.error ? config.i18n.error : 'Import failed.'), true);
                })
                .finally(function () {
                    setButtonLoading(button, false);
                });
        });
    }

    function scrollToToday(root) {
        var wrap = root.querySelector('.twmp-revenue__table-wrap');
        var todayCell = root.querySelector('td.is-today[data-shift="morning"], th.is-today[data-shift="morning"]');
        var sticky = root.querySelector('.twmp-revenue__sticky');

        if (!wrap || !todayCell) {
            return;
        }

        var stickyWidth = sticky ? sticky.getBoundingClientRect().width : 0;
        var targetLeft = todayCell.offsetLeft - stickyWidth - 8;

        wrap.scrollLeft = Math.max(0, targetLeft);
    }

    function init(root) {
        root.querySelectorAll('[data-revenue-money]').forEach(function (input) {
            input.addEventListener('input', function () {
                updateTotals(root, input.getAttribute('data-date'), input.getAttribute('data-shift'));
            });

            input.addEventListener('blur', function () {
                var value = digits(input.value);
                input.value = value ? money(value) : '';
            });
        });

        root.querySelectorAll('[data-revenue-total]').forEach(function (output) {
            updateTotals(root, output.getAttribute('data-date'), output.getAttribute('data-shift'));
        });

        bindImport(root);
        window.setTimeout(function () {
            scrollToToday(root);
        }, 0);

        var form = root.querySelector('[data-revenue-form]');

        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var config = window.twmpRevenueShifts || {};
            var body = new FormData();
            var saveButton = form.querySelector('.twmp-revenue__save');

            setButtonLoading(saveButton, true, config.i18n && config.i18n.saving ? config.i18n.saving : 'Đang lưu...');
            setStatus(root, config.i18n && config.i18n.saving ? config.i18n.saving : 'Saving...', false);
            body.append('action', 'twmp_revenue_save_month');
            body.append('nonce', config.nonce || '');
            body.append('branch_id', root.querySelector('[data-revenue-branch]').value || '');
            body.append('month', root.querySelector('[data-revenue-month]').value || '');
            body.append('entries', JSON.stringify(collectEntries(root)));

            fetch(config.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: body
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (!payload || !payload.success) {
                        throw new Error(payload && payload.data && payload.data.message ? payload.data.message : '');
                    }

                    setStatus(root, payload.data && payload.data.message ? payload.data.message : config.i18n.saved, false);
                })
                .catch(function (error) {
                    setStatus(root, error.message || (config.i18n && config.i18n.error ? config.i18n.error : 'Save failed.'), true);
                })
                .finally(function () {
                    setButtonLoading(saveButton, false);
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-revenue-root]').forEach(init);
    });
})();
