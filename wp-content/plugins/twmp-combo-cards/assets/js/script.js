(function($){
    function showMessage(target, message, type) {
        $(target).html('<div class="twmp-combo-' + type + '">' + message + '</div>');
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function(match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[match];
        });
    }

    function renderCard(card, allowActions) {
        var statusClass = escapeHtml(card.status);
        var html = '<div class="twmp-combo-card">';
        html += '<h3>' + escapeHtml(card.customer_name) + ' (' + escapeHtml(card.phone) + ')</h3>';
        if (card.item_name) {
            html += '<p>Tên món: ' + escapeHtml(card.item_name) + '</p>';
        }
        html += '<p>Combo: ' + escapeHtml(card.combo_size) + ' lượt – Trạng thái: <span class="twmp-combo-status ' + statusClass + '">' + escapeHtml(card.status) + '</span></p>';
        html += '<p>Ngày mua: ' + escapeHtml(card.purchased_at) + ' • Hết hạn: ' + escapeHtml(card.expires_at) + '</p>';
        html += '<div class="twmp-combo-slots">';
        var nextSlot = 1;
        while (card.used_slots.indexOf(nextSlot) !== -1) {
            nextSlot++;
        }
        var maxUsedSlot = card.used_slots.length ? Math.max.apply(null, card.used_slots) : 0;
        for (var i = 1; i <= card.combo_size; i++) {
            var used = card.used_slots.indexOf(i) !== -1;
            var slotClass = 'twmp-combo-slot ' + (used ? 'completed' : 'active');
            var disabled = false;

            if (allowActions) {
                if (used) {
                    if (i !== maxUsedSlot) {
                        disabled = true;
                    }
                } else if (i !== nextSlot) {
                    disabled = true;
                }
            } else {
                disabled = true;
            }

            if (disabled) {
                slotClass += ' disabled';
            }

            html += '<button class="' + slotClass + '" data-card="' + card.id + '" data-slot="' + i + '" type="button">' + i + '</button>';
        }
        html += '</div>';

        if ( allowActions && card.logs && card.logs.length ) {
            html += '<button type="button" class="twmp-combo-history-toggle">Xem lịch sử thao tác (' + card.logs.length + ')</button>';
            html += '<div class="twmp-combo-history">';
            html += '<h4>Lịch sử thao tác</h4>';
            html += '<ul>';
            card.logs.forEach(function(log){
                var label = log.action === 'tick' ? 'Tick' : 'Bỏ tick';
                html += '<li>' + escapeHtml(log.performed_at) + ' — ' + escapeHtml(log.performed_by_name) + ' — ' + label + ' slot ' + escapeHtml(log.slot) + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    function normalizePhone(phone) {
        return phone.replace(/[^0-9+]/g, '').replace(/^\+84/, '0');
    }

    $(document).on('submit', '#twmp-combo-lookup-form', function(e){
        e.preventDefault();
        var phone = normalizePhone($('#twmp-combo-phone').val());
        if (!phone) {
            showMessage('#twmp-combo-lookup-result', 'Số điện thoại không hợp lệ.', 'error');
            return;
        }
        showMessage('#twmp-combo-lookup-result', 'Đang tìm...', 'success');
        $.ajax({
            url: TWMPComboCards.restUrl + '/lookup',
            method: 'POST',
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', TWMPComboCards.nonce); },
            data: { phone: phone },
            success: function(response){
                if (!response.cards || response.cards.length === 0) {
                    showMessage('#twmp-combo-lookup-result', 'Không tìm thấy combo nào với số điện thoại này.', 'error');
                    return;
                }
                var html = '';
                response.cards.forEach(function(card){ html += renderCard(card, false); });
                $('#twmp-combo-lookup-result').html(html);
            },
            error: function(){
                showMessage('#twmp-combo-lookup-result', 'Có lỗi xảy ra khi tra cứu.', 'error');
            }
        });
    });

    $(document).on('submit', '#twmp-combo-create-form', function(e){
        e.preventDefault();
        var $form = $(this);
        var data = {
            customer_name: $('#twmp-combo-customer-name').val(),
            phone: normalizePhone($('#twmp-combo-phone').val()),
            item_name: $('#twmp-combo-item-name').val(),
            combo_size: parseInt($('#twmp-combo-size').val(), 10)
        };
        if (!data.customer_name || !data.phone) {
            showMessage('#twmp-combo-manage-results', 'Vui lòng nhập tên và số điện thoại.', 'error');
            return;
        }
        $.ajax({
            url: TWMPComboCards.restUrl + '/cards',
            method: 'POST',
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', TWMPComboCards.nonce); },
            data: data,
            success: function(response){
                showMessage('#twmp-combo-manage-results', 'Tạo combo thành công.', 'success');
                $('#twmp-combo-create-form')[0].reset();
                $('#twmp-combo-search-form').submit();
            },
            error: function(xhr){
                var message = 'Có lỗi khi tạo combo.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showMessage('#twmp-combo-manage-results', message, 'error');
            }
        });
    });

    $(document).on('submit', '#twmp-combo-search-form', function(e){
        e.preventDefault();
        var query = $('#twmp-combo-search-query').val();
        $.ajax({
            url: TWMPComboCards.restUrl + '/cards',
            method: 'GET',
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', TWMPComboCards.nonce); },
            data: { query: query },
            success: function(response){
                if (!response.cards || response.cards.length === 0) {
                    showMessage('#twmp-combo-manage-results', 'Không tìm thấy combo nào.', 'error');
                    return;
                }
                var html = '';
                response.cards.forEach(function(card){ html += renderCard(card, true); });
                $('#twmp-combo-manage-results').html(html);
            },
            error: function(){
                showMessage('#twmp-combo-manage-results', 'Có lỗi khi tìm kiếm combo.', 'error');
            }
        });
    });

    $(document).on('click', '.twmp-combo-slot', function(){
        var $button = $(this);
        if ($button.hasClass('disabled')) {
            return;
        }
        var $card = $button.closest('.twmp-combo-card');
        var cardId = $button.data('card');
        var slot = $button.data('slot');
        var action = $button.hasClass('completed') ? 'DELETE' : 'POST';

        $card.addClass('loading');
        $.ajax({
            url: TWMPComboCards.restUrl + '/cards/' + cardId + '/slots/' + slot,
            method: action,
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', TWMPComboCards.nonce); },
            success: function() {
                $('#twmp-combo-search-form').submit();
            },
            error: function(xhr){
                var message = 'Có lỗi khi cập nhật slot.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showMessage('#twmp-combo-manage-results', message, 'error');
            },
            complete: function() {
                $card.removeClass('loading');
            }
        });
    });

    $(document).on('click', '.twmp-combo-history-toggle', function() {
        var $button = $(this);
        var $card = $button.closest('.twmp-combo-card');
        $card.toggleClass('history-open');
        $button.text($card.hasClass('history-open') ? 'Ẩn lịch sử thao tác' : 'Xem lịch sử thao tác (' + $card.find('.twmp-combo-history li').length + ')');
    });

    $(document).ready(function(){
        if ($('#twmp-combo-search-form').length) {
            $('#twmp-combo-search-form').submit();
        }
    });
})(jQuery);
