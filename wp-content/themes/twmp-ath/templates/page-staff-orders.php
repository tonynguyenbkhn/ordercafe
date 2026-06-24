<?php

/**
 * Template Name: Staff Orders
 * Template Post Type: page
 *
 * @package twmp-ath
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$branch_id        = function_exists('twmp_staff_orders_get_query_branch_id') ? twmp_staff_orders_get_query_branch_id() : 0;
$branch_options   = function_exists('twmp_staff_orders_get_branch_options') ? twmp_staff_orders_get_branch_options(true) : array();
$status_filter    = function_exists('twmp_staff_orders_get_query_status') ? twmp_staff_orders_get_query_status() : 'all';
$order_id_filter  = function_exists('twmp_staff_orders_get_query_order_id') ? twmp_staff_orders_get_query_order_id() : 0;
$order_date_filter = function_exists('twmp_staff_orders_get_query_order_date') ? twmp_staff_orders_get_query_order_date() : current_time('Y-m-d');
$orders           = function_exists('twmp_staff_orders_get_orders_with_fallback') ? twmp_staff_orders_get_orders_with_fallback() : (function_exists('twmp_staff_orders_get_orders') ? twmp_staff_orders_get_orders() : array());
$allowed_statuses = function_exists('twmp_staff_orders_get_allowed_statuses') ? twmp_staff_orders_get_allowed_statuses() : array();
$payment_methods  = function_exists('twmp_staff_orders_get_payment_methods') ? twmp_staff_orders_get_payment_methods() : array('cod' => __('Tiá»n máº·t', 'twmp-ath'), 'bacs' => __('Chuyá»ƒn khoáº£n', 'twmp-ath'));
$board_statuses   = function_exists('twmp_staff_orders_get_board_statuses') ? twmp_staff_orders_get_board_statuses() : array('processing', 'completed');
$can_manage_all   = function_exists('twmp_staff_orders_current_user_can_manage_all_orders') && twmp_staff_orders_current_user_can_manage_all_orders();
$orders_signature = function_exists('twmp_staff_orders_get_orders_signature') ? twmp_staff_orders_get_orders_signature($orders) : '';
?>

<style>
    .twmp-staff-orders {
        background: #f6f7f9;
        min-height: 72vh;
        padding: 48px 0;
    }

    .twmp-staff-orders__container {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .twmp-staff-orders__header {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-end;
        margin-bottom: 10px;
    }

    .twmp-staff-orders__eyebrow {
        color: #52616f;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .08em;
        margin: 0 0 6px;
        text-transform: uppercase;
    }

    .twmp-staff-orders__title {
        color: #18212b;
        font-size: clamp(28px, 4vw, 42px);
        line-height: 1.1;
        margin: 0;
    }

    .twmp-staff-orders__panel {
        background: transparent;
        border: 0;
        border-radius: 8px;
        overflow: hidden;
    }

    .twmp-staff-orders__filters {
        display: flex;
        gap: 12px;
        align-items: end;
        padding: 18px;
        border-bottom: 1px solid #e4e8ee;
    }

    .twmp-staff-orders__field {
        display: grid;
        gap: 6px;
        min-width: 180px;
        width: 48%;
    }

    .twmp-staff-orders__field label {
        color: #52616f;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .twmp-staff-orders select,
    .twmp-staff-orders input[type="date"],
    .twmp-staff-orders__button {
        border: 1px solid #cfd8e3;
        border-radius: 6px;
        min-height: 40px;
        padding: 0 12px;
    }

    .twmp-staff-orders__button {
        background: #18212b;
        color: #fff;
        cursor: pointer;
        font-weight: 700;
    }

    .twmp-staff-orders__notice {
        background: #e7f8ee;
        border: 1px solid #b9e7ca;
        border-radius: 6px;
        color: #155b31;
        margin-bottom: 16px;
        padding: 12px 14px;
    }

    .twmp-staff-orders__message {
        background: #fff;
        border: 1px solid #dde3ea;
        border-radius: 8px;
        padding: 24px;
    }

    .twmp-staff-orders__sound-panel {
        align-items: center;
        background: #fff;
        border: 1px solid #dde3ea;
        border-radius: 8px;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 16px;
        padding: 12px 14px;
    }

    .twmp-staff-orders__sound-panel p {
        color: #52616f;
        font-size: 13px;
        line-height: 1.4;
        margin: 0;
    }

    .twmp-staff-orders__sound-button {
        background: #18212b;
        border: 0;
        border-radius: 6px;
        color: #fff;
        cursor: pointer;
        flex: 0 0 auto;
        font-size: 13px;
        font-weight: 800;
        min-height: 38px;
        padding: 0 12px;
    }

    .twmp-staff-orders__sound-panel.is-enabled .twmp-staff-orders__sound-button {
        background: #166534;
    }

    .twmp-staff-orders__toast {
        background: #18212b;
        border-radius: 8px;
        bottom: 70px;
        box-shadow: 0 14px 38px rgba(16, 24, 40, .22);
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        left: 50%;
        max-width: min(360px, calc(100vw - 32px));
        opacity: 0;
        padding: 13px 16px;
        pointer-events: none;
        position: fixed;
        text-align: center;
        transform: translate(-50%, 12px);
        transition: opacity .2s ease, transform .2s ease;
        width: max-content;
        z-index: 9999;
    }

    .twmp-staff-orders__toast.is-visible {
        opacity: 1;
        transform: translate(-50%, 0);
    }

    /* .twmp-staff-orders__table-wrap {
        overflow-x: auto;
    } */

    .twmp-staff-orders__table {
        border-collapse: collapse;
        margin: 0;
        width: 100%;
    }

    .twmp-staff-orders__table th,
    .twmp-staff-orders__table td {
        border-bottom: 1px solid #e4e8ee;
        padding: 7px 10px;
        text-align: left;
        vertical-align: top;
        font-size: 14px;
    }

    .twmp-staff-orders__table th {
        background: #fbfcfd;
        color: #52616f;
        font-size: 12px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .twmp-staff-orders__order {
        color: #18212b;
        font-weight: 800;
        text-decoration: none;
    }

    .twmp-staff-orders__items {
        margin: 0;
        padding-left: 0;
    }

    .twmp-staff-orders__item-name {
        display: inline;
        font-weight: bold;
    }

    .wc-processing {
        background: #fef3c7;
        color: #92400e;
    }

    .wc-completed {
        background: #dcfce7;
        color: #166534;
    }

    .twmp-staff-orders__item-meta {
        /* display: none; */
    }

    .twmp-staff-orders__meta-button {
        background: #eef3f7;
        border: 1px solid #ccd7e2;
        border-radius: 999px;
        color: #263442;
        cursor: pointer;
        font-size: 12px;
        font-weight: 800;
        margin-left: 6px;
        min-height: 28px;
        padding: 0 10px;
    }

    .twmp-staff-orders__meta {
        color: #52616f;
        display: block;
        font-size: 12px;
        margin-top: 0;
        display: none;
    }

    .twmp-staff-orders__status {
        border-radius: 999px;
        display: inline-block;
        font-size: 12px;
        font-weight: 800;
        padding: 5px 10px;
        background: #edf1f5;
        color: #263442;
    }

    .twmp-staff-orders__status-form {
        display: flex;
        gap: 8px;
        min-width: 230px;
    }

    .twmp-staff-orders__payment-form {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .twmp-staff-orders__payment-button {
        background: #eef3f7;
        border: 1px solid #cfd8e3;
        border-radius: 6px;
        color: #263442;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        min-height: 32px;
        padding: 0 10px;
    }

    .twmp-staff-orders__payment-button.is-active,
    .twmp-staff-orders__payment-button:disabled {
        background: #18212b;
        border-color: #18212b;
        color: #fff;
        cursor: default;
    }

    .twmp-staff-orders__payment-button.is-pending,
    .twmp-staff-orders__status-form.is-pending select {
        opacity: .58;
        pointer-events: none;
    }

    .twmp-staff-orders__row-updated {
        animation: twmpStaffOrderUpdated .7s ease;
    }

    @keyframes twmpStaffOrderUpdated {
        0% {
            background: #fff4d6;
        }

        100% {
            background: transparent;
        }
    }

    .twmp-staff-orders__dialog {
        border: 0;
        border-radius: 8px;
        box-shadow: 0 24px 70px rgba(21, 31, 42, .22);
        max-width: min(420px, calc(100vw - 32px));
        padding: 0;
        width: 100%;
    }

    .twmp-staff-orders__dialog::backdrop {
        background: rgba(16, 24, 32, .42);
    }

    .twmp-staff-orders__dialog-head {
        align-items: center;
        border-bottom: 1px solid #e4e8ee;
        display: flex;
        justify-content: space-between;
        padding: 14px 16px;
    }

    .twmp-staff-orders__dialog-title {
        color: #18212b;
        font-size: 16px;
        font-weight: 800;
        margin: 0;
    }

    .twmp-staff-orders__dialog-close {
        background: transparent;
        border: 0;
        color: #52616f;
        cursor: pointer;
        font-size: 24px;
        line-height: 1;
        padding: 2px 6px;
    }

    .twmp-staff-orders__dialog-body {
        max-height: min(60vh, 420px);
        overflow-y: auto;
        padding: 16px;
    }

    .twmp-staff-orders__dialog-body .wc-item-meta {
        margin: 0;
        padding: 0;
        font-size: 13px;

        li {

            strong,
            p {
                font-size: 13px;
            }
        }
    }

    .wc-item-meta {
        margin: 0;
        padding: 0;
        font-size: 13px;
        display: flex;
        gap: 6px;
        align-items: center;
        flex-flow: row wrap;

        li {
            display: flex;
            gap: 3px;
            align-items: center;

            strong,
            p {
                font-size: 13px;
            }
        }
    }

    .wc-item-meta li:first-child {
        width: 100%;
    }

    @media (max-width: 760px) {
        .twmp-staff-orders {
            padding: 10px 0;
        }

        .twmp-staff-orders__header,
        .twmp-staff-orders__filters,
        .twmp-staff-orders__status-form {
            align-items: stretch;
            flex-direction: column;
        }

        .twmp-staff-orders__filters {
            padding: 10px 0;
            margin-bottom: 10px;
            flex-flow: row wrap;
        }

        .twmp-staff-orders__sound-panel {
            align-items: stretch;
            flex-direction: column;
        }

        .twmp-staff-orders select,
        .twmp-staff-orders__button {
            width: 100%;
        }

        .twmp-staff-orders__table tr {
            display: flex;
            flex-flow: wrap;
            border: 1px solid #aaa;
            margin-bottom: 10px;

            td {
                &:nth-child(1) {
                    width: 50px;
                }

                &.no-order {
                    width: 100%;
                }

                &:nth-child(2) {
                    width: 50px;
                }

                &:nth-child(3) {
                    width: calc(100% - 170px);
                    display: flex;
                    gap: 10px;
                }

                &:nth-child(4) {
                    width: calc(100% - 20px);
                }

                &:nth-child(5) {
                    width: 50px;
                }

                &:nth-child(7) {
                    width: calc(100% - 100px);
                }
            }
        }

        .twmp-staff-orders__table thead {
            display: none;
        }
    }
</style>

<div
    class="twmp-staff-orders"
    data-staff-orders-root
    data-staff-orders-signature="<?php echo esc_attr($orders_signature); ?>"
    data-staff-orders-rest-url="<?php echo esc_url(rest_url('twmp-ath/v1/staff-orders')); ?>"
    data-staff-orders-rest-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>">
    <div class="twmp-staff-orders__container">
        <header class="twmp-staff-orders__header">
            <div>
                <p class="twmp-staff-orders__eyebrow"><?php esc_html_e('Staff area', 'twmp-ath'); ?></p>
                <h1 class="twmp-staff-orders__title"><?php the_title(); ?></h1>
            </div>
        </header>

        <?php if (!function_exists('wc_get_orders')) : ?>
            <div class="twmp-staff-orders__message"><?php esc_html_e('WooCommerce is required for this page.', 'twmp-ath'); ?></div>
        <?php elseif (!is_user_logged_in()) : ?>
            <?php
            echo function_exists('twmp_render_access_notice') ? twmp_render_access_notice(array(
                'type'        => 'login',
                'title'       => __('Vui lÃ²ng Ä‘Äƒng nháº­p', 'twmp-ath'),
                'message'     => __('Báº¡n cáº§n Ä‘Äƒng nháº­p báº±ng tÃ i khoáº£n Ä‘Æ°á»£c cáº¥p quyá»n Ä‘á»ƒ xem khu vá»±c nÃ y.', 'twmp-ath'),
                'action_url'  => wp_login_url(get_permalink()),
                'action_text' => __('ÄÄƒng nháº­p', 'twmp-ath'),
            )) : '<div class="twmp-staff-orders__message">' . esc_html__('Vui lÃ²ng Ä‘Äƒng nháº­p Ä‘á»ƒ xem khu vá»±c nÃ y.', 'twmp-ath') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        <?php elseif (!twmp_staff_orders_current_user_can_view_board()) : ?>
            <?php
            echo function_exists('twmp_render_access_notice') ? twmp_render_access_notice(array(
                'title'   => __('Báº¡n khÃ´ng cÃ³ quyá»n truy cáº­p', 'twmp-ath'),
                'message' => __('TÃ i khoáº£n cá»§a báº¡n chÆ°a Ä‘Æ°á»£c cáº¥p quyá»n xem Ä‘Æ¡n chá» hoáº·c chÆ°a Ä‘Æ°á»£c gÃ¡n chi nhÃ¡nh.', 'twmp-ath'),
            )) : '<div class="twmp-staff-orders__message">' . esc_html__('Báº¡n khÃ´ng cÃ³ quyá»n truy cáº­p khu vá»±c nÃ y.', 'twmp-ath') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        <?php else : ?>
            <?php if (!empty($_GET['twmp_staff_updated'])) : ?>
                <div class="twmp-staff-orders__notice"><?php esc_html_e('Order status updated.', 'twmp-ath'); ?></div>
            <?php endif; ?>
            <?php if (!empty($_GET['twmp_staff_payment_updated'])) : ?>
                <div class="twmp-staff-orders__notice"><?php esc_html_e('Payment method updated.', 'twmp-ath'); ?></div>
            <?php endif; ?>

            <div class="twmp-staff-orders__sound-panel" data-staff-orders-sound-panel>
                <p data-staff-orders-sound-status>Am bao dang tat. Bam de bat tieng khi co don moi hoac don hoan tat.</p>
                <button class="twmp-staff-orders__sound-button" type="button" data-staff-orders-enable-sound>Bat am bao</button>
            </div>
            <div class="twmp-staff-orders__toast" data-staff-orders-toast role="status" aria-live="polite"></div>

            <section class="twmp-staff-orders__panel">
                <form class="twmp-staff-orders__filters" method="get">
                    <?php if ($can_manage_all) : ?>
                        <div class="twmp-staff-orders__field">
                            <label for="branch_id"><?php esc_html_e('Branch', 'twmp-ath'); ?></label>
                            <select name="branch_id" id="branch_id">
                                <option value="0"><?php esc_html_e('All branches', 'twmp-ath'); ?></option>
                                <?php foreach ($branch_options as $id => $label) : ?>
                                    <?php if ('' === $id) {
                                        continue;
                                    } ?>
                                    <option value="<?php echo esc_attr($id); ?>" <?php selected((string) $branch_id, (string) $id); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="twmp-staff-orders__field">
                        <label for="order_date"><?php esc_html_e('Date', 'twmp-ath'); ?></label>
                        <input type="date" name="order_date" id="order_date" value="<?php echo esc_attr($order_date_filter); ?>">
                    </div>
                    <div class="twmp-staff-orders__field">
                        <label for="order_status"><?php esc_html_e('Status', 'twmp-ath'); ?></label>
                        <select name="order_status" id="order_status">
                            <option value="all" <?php selected($status_filter, 'all'); ?>><?php esc_html_e('All', 'twmp-ath'); ?></option>
                            <?php foreach (wc_get_order_statuses() as $status_key => $status_label) : ?>
                                <?php $status_value = str_replace('wc-', '', $status_key); ?>
                                <?php if (!in_array($status_value, $board_statuses, true)) {
                                    continue;
                                } ?>
                                <option value="<?php echo esc_attr($status_value); ?>" <?php selected($status_filter, $status_value); ?>>
                                    <?php echo esc_html($status_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="twmp-staff-orders__button" type="submit"><?php esc_html_e('Filter', 'twmp-ath'); ?></button>
                </form>

                <div class="twmp-staff-orders__table-wrap">
                    <table class="twmp-staff-orders__table">
                        <thead>
                            <tr>
                                <th style="width: 70px;"><?php esc_html_e('Order', 'twmp-ath'); ?></th>
                                <th style="width: 45px;"><?php esc_html_e('Time', 'twmp-ath'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Customer', 'twmp-ath'); ?></th>
                                <th><?php esc_html_e('Items', 'twmp-ath'); ?></th>
                                <th style="width: 45px;"><?php esc_html_e('Total', 'twmp-ath'); ?></th>
                                <th style="display: none;"><?php esc_html_e('Status', 'twmp-ath'); ?></th>
                                <th style="width: 200px;"><?php esc_html_e('Update', 'twmp-ath'); ?></th>
                            </tr>
                        </thead>
                        <tbody data-staff-orders-body>
                            <?php echo function_exists('twmp_staff_orders_render_table_rows') ? twmp_staff_orders_render_table_rows($orders) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <dialog class="twmp-staff-orders__dialog" data-staff-order-meta-dialog>
                <div class="twmp-staff-orders__dialog-head">
                    <h2 class="twmp-staff-orders__dialog-title" data-staff-order-meta-title><?php esc_html_e('Item details', 'twmp-ath'); ?></h2>
                    <button type="button" class="twmp-staff-orders__dialog-close" data-staff-order-meta-close aria-label="<?php esc_attr_e('Close', 'twmp-ath'); ?>">&times;</button>
                </div>
                <div class="twmp-staff-orders__dialog-body" data-staff-order-meta-body></div>
            </dialog>

            <script>
                (function() {
                    var dialog = document.querySelector('[data-staff-order-meta-dialog]');
                    if (dialog) {
                        var title = dialog.querySelector('[data-staff-order-meta-title]');
                        var body = dialog.querySelector('[data-staff-order-meta-body]');
                        var close = dialog.querySelector('[data-staff-order-meta-close]');

                        document.addEventListener('click', function(event) {
                            var trigger = event.target.closest('[data-staff-order-meta-trigger]');
                            if (!trigger) {
                                return;
                            }

                            var item = trigger.closest('li');
                            var meta = item ? item.querySelector('.twmp-staff-orders__item-meta') : null;
                            var itemName = item ? item.querySelector('.twmp-staff-orders__item-name') : null;

                            if (!meta || !body) {
                                return;
                            }

                            title.textContent = itemName ? itemName.textContent.trim() : '<?php echo esc_js(__('Item details', 'twmp-ath')); ?>';
                            body.innerHTML = meta.innerHTML;

                            if (typeof dialog.showModal === 'function') {
                                dialog.showModal();
                            } else {
                                dialog.setAttribute('open', 'open');
                            }
                        });

                        close.addEventListener('click', function() {
                            dialog.close();
                        });

                        dialog.addEventListener('click', function(event) {
                            if (event.target === dialog) {
                                dialog.close();
                            }
                        });
                    }

                    var root = document.querySelector('[data-staff-orders-root]');
                    var filters = root ? root.querySelector('.twmp-staff-orders__filters') : null;
                    var ordersBody = root ? root.querySelector('[data-staff-orders-body]') : null;
                    var signature = root ? root.getAttribute('data-staff-orders-signature') : '';
                    var pollRequest = null;
                    var soundPanel = root ? root.querySelector('[data-staff-orders-sound-panel]') : null;
                    var soundButton = root ? root.querySelector('[data-staff-orders-enable-sound]') : null;
                    var soundStatus = root ? root.querySelector('[data-staff-orders-sound-status]') : null;
                    var toast = root ? root.querySelector('[data-staff-orders-toast]') : null;
                    var toastTimer = null;
                    var audioContext = null;
                    var soundStorageKey = 'twmp_staff_orders_sound_enabled';
                    var soundEnabled = window.localStorage && localStorage.getItem(soundStorageKey) === '1';

                    if (!root || !filters || !ordersBody) {
                        return;
                    }

                    function getRowStatus(row) {
                        var statusClass = row ? Array.from(row.classList).find(function(className) {
                            return className.indexOf('wc-') === 0;
                        }) : '';

                        if (statusClass) {
                            return statusClass.replace(/^wc-/, '');
                        }

                        var select = row ? row.querySelector('[name="twmp_order_status"]') : null;

                        return select && select.value ? select.value : '';
                    }

                    function collectOrderState(container) {
                        var state = {};

                        container.querySelectorAll('[data-staff-order-row]').forEach(function(row) {
                            var orderId = row.getAttribute('data-order-id');

                            if (!orderId) {
                                return;
                            }

                            state[orderId] = {
                                status: getRowStatus(row)
                            };
                        });

                        return state;
                    }

                    function showToast(message) {
                        if (!toast) {
                            return;
                        }

                        toast.textContent = message || '';
                        toast.classList.add('is-visible');
                        window.clearTimeout(toastTimer);
                        toastTimer = window.setTimeout(function() {
                            toast.classList.remove('is-visible');
                        }, 4200);
                    }

                    function ensureAudioContext() {
                        if (!audioContext) {
                            var AudioCtor = window.AudioContext || window.webkitAudioContext;

                            if (!AudioCtor) {
                                return null;
                            }

                            audioContext = new AudioCtor();
                        }

                        if (audioContext.state === 'suspended') {
                            audioContext.resume();
                        }

                        return audioContext;
                    }

                    function playTone(frequency, startOffset, duration) {
                        var context = ensureAudioContext();

                        if (!context) {
                            return;
                        }

                        var start = context.currentTime + startOffset;
                        var oscillator = context.createOscillator();
                        var gain = context.createGain();

                        oscillator.type = 'sine';
                        oscillator.frequency.value = frequency;
                        oscillator.connect(gain);
                        gain.connect(context.destination);
                        gain.gain.setValueAtTime(0.0001, start);
                        gain.gain.exponentialRampToValueAtTime(0.18, start + 0.018);
                        gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
                        oscillator.start(start);
                        oscillator.stop(start + duration + 0.03);
                    }

                    function playNotificationSound(type) {
                        if (!soundEnabled) {
                            return;
                        }

                        if (type === 'completed') {
                            playTone(740, 0, 0.16);
                            playTone(988, 0.2, 0.18);
                            return;
                        }

                        playTone(880, 0, 0.14);
                        playTone(1175, 0.18, 0.16);
                    }

                    function setSoundEnabled(enabled, playTestSound) {
                        soundEnabled = !!enabled;

                        if (window.localStorage) {
                            localStorage.setItem(soundStorageKey, soundEnabled ? '1' : '0');
                        }

                        if (soundPanel) {
                            soundPanel.classList.toggle('is-enabled', soundEnabled);
                        }

                        if (soundStatus) {
                            soundStatus.textContent = soundEnabled
                                ? 'Âm báo đã bật'
                                : 'Âm báo đang tắt.';
                        }

                        if (soundButton) {
                            soundButton.textContent = soundEnabled ? 'Đã bật âm báo' : 'Bật âm báo';
                            soundButton.disabled = soundEnabled;
                        }

                        if (!soundEnabled) {
                            return;
                        }

                        ensureAudioContext();

                        if (playTestSound) {
                            playTone(880, 0, 0.12);
                            playTone(1175, 0.16, 0.14);
                        }

                        if (window.Notification && Notification.permission === 'default') {
                            Notification.requestPermission();
                        }
                    }

                    function notifyStaff(type, orderId) {
                        var message = type === 'completed'
                            ? 'Đơn #' + orderId + ' đã hoàn tất.'
                            : 'Có đơn mới #' + orderId + '.';

                        showToast(message);
                        playNotificationSound(type);

                        if (document.hidden && window.Notification && Notification.permission === 'granted') {
                            new Notification(message);
                        }
                    }

                    function notifyStateChanges(previousState, nextState) {
                        Object.keys(nextState).forEach(function(orderId) {
                            var previous = previousState[orderId] || null;
                            var current = nextState[orderId] || null;

                            if (!current) {
                                return;
                            }

                            if (previous.status !== 'completed' && current.status === 'completed') {
                                notifyStaff('completed', orderId);
                            }
                        });
                    }

                    if (soundButton) {
                        soundButton.addEventListener('click', function() {
                            setSoundEnabled(true, true);
                        });
                    }

                    setSoundEnabled(soundEnabled, false);

                    if (soundEnabled) {
                        document.addEventListener('pointerdown', function() {
                            ensureAudioContext();
                        }, {
                            once: true,
                            passive: true
                        });
                    }

                    function notifyCreatedOrderFromUrl() {
                        if (!window.URLSearchParams) {
                            return;
                        }

                        var params = new URLSearchParams(window.location.search);
                        if (params.get('twmp_order_created') !== '1') {
                            return;
                        }

                        var orderId = params.get('twmp_order_id') || '';
                        notifyStaff('new', orderId);
                        params.delete('twmp_order_created');

                        if (window.history && window.history.replaceState) {
                            var nextQuery = params.toString();
                            var nextUrl = window.location.pathname + (nextQuery ? '?' + nextQuery : '');
                            window.history.replaceState(null, '', nextUrl);
                        }
                    }

                    notifyCreatedOrderFromUrl();

                    function formDataToObject(formData) {
                        var data = {};

                        new FormData(filters).forEach(function(value, key) {
                            data[key] = value;
                        });

                        formData.forEach(function(value, key) {
                            data[key] = value;
                        });

                        return data;
                    }

                    function restUrl(params) {
                        var url = root.getAttribute('data-staff-orders-rest-url') || '/wp-json/twmp-ath/v1/staff-orders';
                        var query = new URLSearchParams(params || {});

                        return query.toString() ? url + '?' + query.toString() : url;
                    }

                    function updateOrder(data) {
                        var url = (root.getAttribute('data-staff-orders-rest-url') || '/wp-json/twmp-ath/v1/staff-orders') + '/update';

                        return fetch(url, {
                            method: 'PUT',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': root.getAttribute('data-staff-orders-rest-nonce') || ''
                            },
                            body: JSON.stringify(data || {})
                        }).then(function(response) {
                            return response.json().then(function(payload) {
                                if (!response.ok) {
                                    throw new Error(payload && payload.message ? payload.message : 'Request failed.');
                                }

                                return payload;
                            });
                        });
                    }

                    function updateUrlFromFilters() {
                        if (!window.history || !window.URLSearchParams) {
                            return;
                        }

                        var params = new URLSearchParams(new FormData(filters));
                        var nextUrl = window.location.pathname + '?' + params.toString();

                        window.history.replaceState(null, '', nextUrl);
                    }

                    function refreshOrders(updateUrl) {
                        if (pollRequest) {
                            pollRequest.abort();
                        }

                        pollRequest = new AbortController();

                        var requestParams = formDataToObject(new FormData(filters));
                        if (!updateUrl && signature) {
                            requestParams.signature = signature;
                        }

                        fetch(restUrl(requestParams), {
                                method: 'GET',
                                credentials: 'same-origin',
                                headers: {
                                    'X-WP-Nonce': root.getAttribute('data-staff-orders-rest-nonce') || ''
                                },
                                signal: pollRequest.signal
                            })
                            .then(function(response) {
                                return response.json().then(function(payload) {
                                    if (!response.ok) {
                                        throw new Error(payload && payload.message ? payload.message : 'Request failed.');
                                    }

                                    return payload;
                                });
                            })
                            .then(function(payload) {
                                var data = payload || null;

                                if (!data) {
                                    return;
                                }

                                if (typeof data.html === 'string' && (updateUrl || data.signature !== signature)) {
                                    var previousState = collectOrderState(ordersBody);

                                    ordersBody.innerHTML = data.html;
                                    signature = data.signature || '';
                                    root.setAttribute('data-staff-orders-signature', signature);

                                    if (!updateUrl) {
                                        notifyStateChanges(previousState, collectOrderState(ordersBody));
                                    }
                                }

                                if (updateUrl) {
                                    updateUrlFromFilters();
                                }
                            })
                            .catch(function(error) {
                                if (!error || 'AbortError' !== error.name) {
                                    // Keep current rows on network errors.
                                }
                            });
                    }

                    function flashRow(row) {
                        row.classList.remove('twmp-staff-orders__row-updated');
                        row.offsetHeight;
                        row.classList.add('twmp-staff-orders__row-updated');
                    }

                    function syncRowStatusClass(row, status) {
                        if (!row || !status) {
                            return;
                        }

                        Array.from(row.classList).forEach(function(className) {
                            if (className.indexOf('wc-') === 0) {
                                row.classList.remove(className);
                            }
                        });

                        row.classList.add('wc-' + status);
                    }

                    function updatePaymentButtons(row, activeMethod) {
                        row.querySelectorAll('.twmp-staff-orders__payment-button').forEach(function(button) {
                            var isActive = button.value === activeMethod;

                            button.classList.toggle('is-active', isActive);
                            button.disabled = isActive;
                            button.classList.remove('is-pending');
                        });
                    }

                    function applyOrderUpdate(row, data) {
                        var statusSelect = row.querySelector('[name="twmp_order_status"]');
                        var statusLabel = row.querySelector('[data-staff-order-status-label]');
                        var paymentLabel = row.querySelector('[data-staff-order-payment-label]');
                        var previousStatus = getRowStatus(row);

                        if (statusSelect && data.status) {
                            statusSelect.value = data.status;
                        }

                        if (statusLabel && data.status_name) {
                            statusLabel.textContent = data.status_name;
                        }

                        if (data.status) {
                            syncRowStatusClass(row, data.status);
                        }

                        if (paymentLabel && data.payment_method_label) {
                            paymentLabel.textContent = 'Payment: ' + data.payment_method_label;
                        }

                        if (data.payment_method) {
                            updatePaymentButtons(row, data.payment_method);
                        }

                        if (data.signature) {
                            signature = data.signature;
                            root.setAttribute('data-staff-orders-signature', signature);
                        }

                        row.querySelectorAll('.is-pending').forEach(function(node) {
                            node.classList.remove('is-pending');
                        });

                        flashRow(row);

                        if (previousStatus !== 'completed' && data.status === 'completed') {
                            notifyStaff('completed', data.order_id || row.getAttribute('data-order-id') || '');
                        }
                    }

                    filters.addEventListener('submit', function(event) {
                        event.preventDefault();
                        refreshOrders(true);
                    });

                    filters.addEventListener('change', function(event) {
                        if (event.target.matches('select, input[type="date"]')) {
                            refreshOrders(true);
                        }
                    });

                    root.addEventListener('change', function(event) {
                        var select = event.target.closest('[data-staff-order-status-form] select');
                        if (!select) {
                            return;
                        }

                        var form = select.form;
                        var row = select.closest('[data-staff-order-row]');
                        var body = new FormData(form);

                        event.preventDefault();
                        form.classList.add('is-pending');
                        body = formDataToObject(body);

                        updateOrder(body)
                            .then(function(payload) {
                                form.classList.remove('is-pending');
                                applyOrderUpdate(row, payload || {});
                            })
                            .catch(function() {
                                form.submit();
                            });
                    });

                    root.addEventListener('submit', function(event) {
                        var form = event.target.closest('[data-staff-order-payment-form]');
                        if (!form) {
                            return;
                        }

                        var submitter = event.submitter || document.activeElement;
                        var row = form.closest('[data-staff-order-row]');
                        var body = new FormData(form);

                        event.preventDefault();

                        if (submitter && submitter.name) {
                            body.set(submitter.name, submitter.value);
                            submitter.classList.add('is-pending');
                        }

                        body = formDataToObject(body);

                        updateOrder(body)
                            .then(function(payload) {
                                applyOrderUpdate(row, payload || {});
                            })
                            .catch(function() {
                                form.submit();
                            });
                    });

                    window.setInterval(function() {
                        refreshOrders(false);
                    }, 15000);
                })();
            </script>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
