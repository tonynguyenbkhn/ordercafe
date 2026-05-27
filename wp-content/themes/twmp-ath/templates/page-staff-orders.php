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
$orders           = function_exists('twmp_staff_orders_get_orders') ? twmp_staff_orders_get_orders() : array();
$allowed_statuses = function_exists('twmp_staff_orders_get_allowed_statuses') ? twmp_staff_orders_get_allowed_statuses() : array();
$board_statuses   = function_exists('twmp_staff_orders_get_board_statuses') ? twmp_staff_orders_get_board_statuses() : array('on-hold', 'processing');
$can_manage_all   = function_exists('twmp_staff_orders_current_user_can_manage_all_orders') && twmp_staff_orders_current_user_can_manage_all_orders();
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
        gap: 24px;
        align-items: flex-end;
        margin-bottom: 24px;
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
        background: #fff;
        border: 1px solid #dde3ea;
        border-radius: 8px;
        box-shadow: 0 12px 40px rgba(21, 31, 42, .06);
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
    }

    .twmp-staff-orders__field label {
        color: #52616f;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .twmp-staff-orders select,
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

    @media (max-width: 760px) {
        .twmp-staff-orders {
            padding: 28px 0;
        }

        .twmp-staff-orders__header,
        .twmp-staff-orders__filters,
        .twmp-staff-orders__status-form {
            align-items: stretch;
            flex-direction: column;
        }

        .twmp-staff-orders__filters {
            padding: 10px;
            margin-bottom: 10px;
        }

        .twmp-staff-orders select, .twmp-staff-orders__button {
            width: 100%;
        }

        .twmp-staff-orders__table tr {
            display: flex;
            flex-flow: wrap;
            border: 1px solid #aaa;
            margin-bottom: 10px;

            td {
                &:nth-child(1) {
                    
                }

                &:nth-child(2) {
                    width: 50px;
                }
                
                &:nth-child(3) {
                    width: calc(100% - 100px);
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

<div class="twmp-staff-orders">
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
            <div class="twmp-staff-orders__message">
                <?php
                printf(
                    wp_kses_post(__('Please <a href="%s">log in</a> to view staff orders.', 'twmp-ath')),
                    esc_url(wp_login_url(get_permalink()))
                );
                ?>
            </div>
        <?php elseif (!twmp_staff_orders_current_user_can_view_board()) : ?>
            <div class="twmp-staff-orders__message"><?php esc_html_e('Your account cannot access Staff Orders or is not assigned to a branch.', 'twmp-ath'); ?></div>
        <?php else : ?>
            <?php if (!empty($_GET['twmp_staff_updated'])) : ?>
                <div class="twmp-staff-orders__notice"><?php esc_html_e('Order status updated.', 'twmp-ath'); ?></div>
            <?php endif; ?>

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
                                <th style="display: none;"><?php esc_html_e('Order', 'twmp-ath'); ?></th>
                                <th style="width: 45px;"><?php esc_html_e('Time', 'twmp-ath'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Customer', 'twmp-ath'); ?></th>
                                <th><?php esc_html_e('Items', 'twmp-ath'); ?></th>
                                <th style="width: 45px;"><?php esc_html_e('Total', 'twmp-ath'); ?></th>
                                <th style="display: none;"><?php esc_html_e('Status', 'twmp-ath'); ?></th>
                                <th style="width: 200px;"><?php esc_html_e('Update', 'twmp-ath'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)) : ?>
                                <tr>
                                    <td colspan="7"><?php esc_html_e('No orders found.', 'twmp-ath'); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($orders as $order) : ?>
                                <?php
                                if (!$order instanceof WC_Order || !twmp_staff_orders_user_can_access_order($order)) {
                                    continue;
                                }

                                $order_date = $order->get_date_created();
                                $status_key = 'wc-' . $order->get_status();
                                ?>
                                <tr>
                                    <td style="display: none;">
                                        <?php if (current_user_can('edit_shop_order', $order->get_id())) : ?>
                                            <a class="twmp-staff-orders__order" href="<?php echo esc_url($order->get_edit_order_url()); ?>">
                                                #<?php echo esc_html($order->get_order_number()); ?>
                                            </a>
                                        <?php else : ?>
                                            <strong class="twmp-staff-orders__order">#<?php echo esc_html($order->get_order_number()); ?></strong>
                                        <?php endif; ?>
                                        <?php if ($can_manage_all && twmp_staff_orders_get_order_branch_id($order)) : ?>
                                            <span class="twmp-staff-orders__meta"><?php echo esc_html(get_the_title(twmp_staff_orders_get_order_branch_id($order))); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($order_date ? $order_date->date_i18n('H:i') : ''); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : __('Guest', 'twmp-ath')); ?>
                                        <?php if ($order->get_billing_phone()) : ?>
                                            <span class="twmp-staff-orders__meta"><?php echo esc_html($order->get_billing_phone()); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <ul class="twmp-staff-orders__items">
                                            <?php foreach ($order->get_items() as $item) : ?>
                                                <li>
                                                    <span class="twmp-staff-orders__item-name"><?php echo esc_html($item->get_name()); ?></span>
                                                    <strong>x<?php echo esc_html($item->get_quantity()); ?></strong>
                                                    <?php
                                                    $item_meta = wc_display_item_meta($item, array('echo' => false));
                                                    if ($item_meta) {
                                                    ?>
                                                        <div class="twmp-staff-orders__item-meta">
                                                            <?php echo wp_kses_post($item_meta); ?>
                                                        </div>
                                                    <?php
                                                    }
                                                    ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
                                    <td style="display: none;"><span class="twmp-staff-orders__status"><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span></td>
                                    <td>
                                        <?php if ('completed' === $order->get_status()) : ?>
                                            <span class="twmp-staff-orders__meta"><?php esc_html_e('Completed orders cannot be changed here.', 'twmp-ath'); ?></span>
                                        <?php else : ?>
                                            <form class="twmp-staff-orders__status-form" method="post">
                                                <?php wp_nonce_field('twmp_staff_order_update_status', 'twmp_staff_order_nonce'); ?>
                                                <input type="hidden" name="twmp_staff_order_action" value="update_status">
                                                <input type="hidden" name="twmp_order_id" value="<?php echo esc_attr($order->get_id()); ?>">
                                                <input type="hidden" name="twmp_staff_redirect" value="<?php echo esc_url(get_permalink()); ?>">
                                                <select name="twmp_order_status" aria-label="<?php esc_attr_e('New order status', 'twmp-ath'); ?>" onchange="this.form.submit();">
                                                    <?php foreach ($allowed_statuses as $allowed_key => $allowed_label) : ?>
                                                        <?php $allowed_value = str_replace('wc-', '', $allowed_key); ?>
                                                        <option value="<?php echo esc_attr($allowed_value); ?>" <?php selected($status_key, $allowed_key); ?>>
                                                            <?php echo esc_html($allowed_label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
                    if (!dialog) {
                        return;
                    }

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
                })();
            </script>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
