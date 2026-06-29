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
        return {
            revenue_actual: digits(entry.cash_sales) + digits(entry.bank_transfer_sales) - digits(entry.expenses_cash)
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

        updateDaySummary(root);
    }

    function updateDaySummary(root) {
        var summaries = {
            cash_sales: 0,
            bank_transfer_sales: 0,
            failed_sales: 0,
            expenses_cash: 0,
            revenue_actual: 0
        };

        root.querySelectorAll('[data-revenue-total][data-field="revenue_actual"]').forEach(function (output) {
            summaries.revenue_actual += digits(output.textContent);
        });

        ['cash_sales', 'bank_transfer_sales', 'failed_sales', 'expenses_cash'].forEach(function (field) {
            root.querySelectorAll('[data-revenue-input][data-revenue-money][data-field="' + field + '"]').forEach(function (input) {
                summaries[field] += digits(input.value);
            });
        });

        Object.keys(summaries).forEach(function (field) {
            var output = root.querySelector('[data-revenue-day-total="' + field + '"]');

            if (output) {
                output.textContent = money(summaries[field]);
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
            return false;
        }

        input.value = value ? money(value) : '';
        updateTotals(root, date, shift);

        return true;
    }

    function applyImportedEntries(root, entries) {
        var applied = 0;

        root.querySelectorAll('[data-revenue-input][data-revenue-money][data-field="cash_sales"], [data-revenue-input][data-revenue-money][data-field="bank_transfer_sales"], [data-revenue-input][data-revenue-money][data-field="failed_sales"]').forEach(function (input) {
            input.value = '';
            updateTotals(root, input.getAttribute('data-date'), input.getAttribute('data-shift'));
        });

        Object.keys(entries || {}).forEach(function (date) {
            Object.keys(entries[date] || {}).forEach(function (shift) {
                var entry = entries[date][shift] || {};

                var cashApplied = setMoneyInput(root, date, shift, 'cash_sales', digits(entry.cash_sales));
                var bankApplied = setMoneyInput(root, date, shift, 'bank_transfer_sales', digits(entry.bank_transfer_sales));
                var failedApplied = setMoneyInput(root, date, shift, 'failed_sales', digits(entry.failed_sales));

                if (cashApplied || bankApplied || failedApplied) {
                    applied += 1;
                }
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
            var staffFilter = root.querySelector('[data-revenue-filter-staff]');
            var body = {
                branch_id: root.querySelector('[data-revenue-branch]').value || '',
                month: root.querySelector('[data-revenue-month]').value || '',
                date: root.querySelector('[data-revenue-date]').value || '',
                staff_user_id: staffFilter ? staffFilter.value || '' : ''
            };

            setButtonLoading(button, true, config.i18n && config.i18n.importing ? config.i18n.importing : 'Importing...');
            setStatus(root, config.i18n && config.i18n.importing ? config.i18n.importing : 'Importing orders...', false);

            fetch((config.restUrl || '/wp-json/twmp-revenue-shifts/v1') + '/import-orders', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': config.nonce || ''
                },
                body: JSON.stringify(body)
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            throw new Error(payload && payload.message ? payload.message : '');
                        }

                        return payload;
                    });
                })
                .then(function (payload) {
                    var applied = applyImportedEntries(root, payload.entries || {});
                    var message = payload && payload.message ? payload.message : 'Imported orders.';

                    if (!applied) {
                        message += ' No shift matched today table.';
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

    function bindFilters(root) {
        var filters = root.querySelector('[data-revenue-filters]');

        if (!filters) {
            return;
        }

        filters.addEventListener('change', function (event) {
            if (event.target.matches('select, input[type="date"]')) {
                filters.submit();
            }
        });
    }

    function bindClearForm(root) {
        var form = root.querySelector('[data-revenue-clear-form]');

        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (!window.confirm('Xoa toan bo data doanh thu va tat ca don hang WooCommerce?')) {
                event.preventDefault();
            }
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
        bindFilters(root);
        bindClearForm(root);
        window.setTimeout(function () {
            scrollToToday(root);
        }, 0);

        if (window.twmpRevenueShifts && window.twmpRevenueShifts.autoImport) {
            window.setTimeout(function () {
                var importButton = root.querySelector('[data-revenue-import]');

                if (importButton) {
                    importButton.click();
                }
            }, 0);
        }

        var form = root.querySelector('[data-revenue-form]');

        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var config = window.twmpRevenueShifts || {};
            var body = {
                branch_id: root.querySelector('[data-revenue-branch]').value || '',
                month: root.querySelector('[data-revenue-month]').value || '',
                entries: collectEntries(root)
            };
            var saveButton = form.querySelector('.twmp-revenue__save');

            setButtonLoading(saveButton, true, config.i18n && config.i18n.saving ? config.i18n.saving : 'Saving...');
            setStatus(root, config.i18n && config.i18n.saving ? config.i18n.saving : 'Saving...', false);

            fetch((config.restUrl || '/wp-json/twmp-revenue-shifts/v1') + '/month', {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': config.nonce || ''
                },
                body: JSON.stringify(body)
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            throw new Error(payload && payload.message ? payload.message : '');
                        }

                        return payload;
                    });
                })
                .then(function (payload) {
                    setStatus(root, payload && payload.message ? payload.message : config.i18n.saved, false);
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
