<?php
/**
 * Plugin Name: TWMP Revenue Shifts
 * Description: Shift-based cafe revenue tracking for OrderCafe.
 * Version: 0.1.4
 * Author: TWMP
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TWMP_REVENUE_SHIFTS_VERSION', '0.1.4');
define('TWMP_REVENUE_SHIFTS_FILE', __FILE__);
define('TWMP_REVENUE_SHIFTS_DIR', plugin_dir_path(__FILE__));
define('TWMP_REVENUE_SHIFTS_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, 'twmp_revenue_shifts_activate');

add_action('wp_enqueue_scripts', 'twmp_revenue_shifts_register_assets');
add_action('admin_enqueue_scripts', 'twmp_revenue_shifts_register_assets');
add_shortcode('twmp_revenue', 'twmp_revenue_shifts_render_shortcode');
add_action('wp_ajax_twmp_revenue_save_month', 'twmp_revenue_shifts_ajax_save_month');
add_action('wp_ajax_twmp_revenue_import_orders', 'twmp_revenue_shifts_ajax_import_orders');

function twmp_revenue_shifts_activate()
{
    twmp_revenue_shifts_create_tables();
    twmp_revenue_shifts_ensure_page();
}

function twmp_revenue_shifts_table()
{
    global $wpdb;

    return $wpdb->prefix . 'twmp_revenue_shifts';
}

function twmp_revenue_shifts_create_tables()
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = twmp_revenue_shifts_table();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        branch_id bigint(20) unsigned NOT NULL,
        business_date date NOT NULL,
        shift_key varchar(20) NOT NULL,
        staff_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
        opening_cash bigint(20) NOT NULL DEFAULT 0,
        cash_sales bigint(20) NOT NULL DEFAULT 0,
        exchange_cash_out bigint(20) NOT NULL DEFAULT 0,
        expenses_cash bigint(20) NOT NULL DEFAULT 0,
        bank_transfer_sales bigint(20) NOT NULL DEFAULT 0,
        note text NULL,
        status varchar(20) NOT NULL DEFAULT 'draft',
        closed_at datetime NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY branch_date_shift (branch_id, business_date, shift_key),
        KEY business_date (business_date),
        KEY staff_user_id (staff_user_id)
    ) {$charset_collate}";

    dbDelta($sql);
    update_option('twmp_revenue_shifts_db_version', TWMP_REVENUE_SHIFTS_VERSION, false);
}

function twmp_revenue_shifts_ensure_page()
{
    $existing_page_id = absint(get_option('twmp_revenue_shifts_page_id', 0));

    if ($existing_page_id && get_post($existing_page_id)) {
        return;
    }

    $page = get_page_by_path('doanh-thu');

    if ($page instanceof WP_Post) {
        update_option('twmp_revenue_shifts_page_id', $page->ID, false);
        return;
    }

    $page_id = wp_insert_post([
        'post_title'   => 'Doanh thu',
        'post_name'    => 'doanh-thu',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '[twmp_revenue]',
    ]);

    if ($page_id && !is_wp_error($page_id)) {
        update_option('twmp_revenue_shifts_page_id', absint($page_id), false);
    }
}

function twmp_revenue_shifts_register_assets()
{
    wp_register_style(
        'twmp-revenue-shifts',
        TWMP_REVENUE_SHIFTS_URL . 'assets/revenue.css',
        [],
        TWMP_REVENUE_SHIFTS_VERSION
    );

    wp_register_script(
        'twmp-revenue-shifts',
        TWMP_REVENUE_SHIFTS_URL . 'assets/revenue.js',
        [],
        TWMP_REVENUE_SHIFTS_VERSION,
        true
    );
}

function twmp_revenue_shifts_is_admin_user()
{
    $user = wp_get_current_user();

    return $user instanceof WP_User && in_array('administrator', (array) $user->roles, true);
}

function twmp_revenue_shifts_is_shop_manager()
{
    $user = wp_get_current_user();

    return $user instanceof WP_User && in_array('shop_manager', (array) $user->roles, true);
}

function twmp_revenue_shifts_get_user_branch_id($user_id = 0)
{
    $user_id = $user_id ? absint($user_id) : get_current_user_id();

    if (function_exists('twmp_staff_orders_get_user_branch_id')) {
        return absint(twmp_staff_orders_get_user_branch_id($user_id));
    }

    return absint(get_user_meta($user_id, 'twmp_staff_branch_id', true));
}

function twmp_revenue_shifts_user_can_view()
{
    if (!is_user_logged_in()) {
        return false;
    }

    if (twmp_revenue_shifts_is_admin_user()) {
        return true;
    }

    return twmp_revenue_shifts_is_shop_manager() && twmp_revenue_shifts_get_user_branch_id();
}

function twmp_revenue_shifts_user_can_access_branch($branch_id)
{
    $branch_id = absint($branch_id);

    if (!$branch_id) {
        return false;
    }

    if (twmp_revenue_shifts_is_admin_user()) {
        return true;
    }

    return twmp_revenue_shifts_is_shop_manager() && $branch_id === twmp_revenue_shifts_get_user_branch_id();
}

function twmp_revenue_shifts_get_branch_options($include_empty = false)
{
    if (function_exists('twmp_staff_orders_get_branch_options')) {
        return twmp_staff_orders_get_branch_options($include_empty);
    }

    $options = $include_empty ? ['' => __('Select branch', 'twmp-revenue-shifts')] : [];
    $branches = get_posts([
        'post_type'      => 'twmp_branch',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    foreach ($branches as $branch) {
        $options[$branch->ID] = get_the_title($branch);
    }

    return $options;
}

function twmp_revenue_shifts_get_selected_branch_id()
{
    if (twmp_revenue_shifts_is_admin_user()) {
        $requested = isset($_GET['branch_id']) ? absint(wp_unslash($_GET['branch_id'])) : 0;

        if ($requested) {
            return $requested;
        }

        $branches = twmp_revenue_shifts_get_branch_options(false);
        $ids = array_keys($branches);

        return !empty($ids) ? absint($ids[0]) : 0;
    }

    return twmp_revenue_shifts_get_user_branch_id();
}

function twmp_revenue_shifts_get_month()
{
    $month = isset($_GET['revenue_month']) ? sanitize_text_field(wp_unslash($_GET['revenue_month'])) : gmdate('Y-m');

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        return gmdate('Y-m');
    }

    return $month;
}

function twmp_revenue_shifts_get_staff_users()
{
    return get_users([
        'role__in' => ['administrator', 'shop_manager'],
        'orderby'  => 'display_name',
        'order'    => 'ASC',
        'fields'   => ['ID', 'display_name'],
    ]);
}

function twmp_revenue_shifts_money_fields()
{
    return [
        'opening_cash',
        'cash_sales',
        'exchange_cash_out',
        'expenses_cash',
        'bank_transfer_sales',
    ];
}

function twmp_revenue_shifts_parse_money($value)
{
    $value = is_scalar($value) ? (string) $value : '0';
    $value = preg_replace('/[^\d-]/', '', $value);

    return (int) $value;
}

function twmp_revenue_shifts_calculate($row)
{
    $opening_cash = isset($row['opening_cash']) ? (int) $row['opening_cash'] : 0;
    $cash_sales = isset($row['cash_sales']) ? (int) $row['cash_sales'] : 0;
    $exchange_cash_out = isset($row['exchange_cash_out']) ? (int) $row['exchange_cash_out'] : 0;
    $expenses_cash = isset($row['expenses_cash']) ? (int) $row['expenses_cash'] : 0;
    $bank_transfer_sales = isset($row['bank_transfer_sales']) ? (int) $row['bank_transfer_sales'] : 0;

    $cash_actual = $cash_sales - $exchange_cash_out - $expenses_cash;
    $bank_actual = $bank_transfer_sales + $exchange_cash_out;

    return [
        'cash_actual'          => $cash_actual,
        'bank_transfer_actual' => $bank_actual,
        'revenue_actual'       => $cash_actual + $bank_actual,
        'drawer_cash_expected' => $opening_cash + $cash_actual,
    ];
}

function twmp_revenue_shifts_get_month_entries($branch_id, $month)
{
    global $wpdb;

    $branch_id = absint($branch_id);
    $start = $month . '-01';
    $end = gmdate('Y-m-t', strtotime($start));
    $table = twmp_revenue_shifts_table();
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE branch_id = %d AND business_date BETWEEN %s AND %s",
            $branch_id,
            $start,
            $end
        ),
        ARRAY_A
    );

    $entries = [];

    foreach ((array) $rows as $row) {
        $entries[$row['business_date']][$row['shift_key']] = $row;
    }

    return $entries;
}

function twmp_revenue_shifts_format_money($value)
{
    return number_format((int) $value, 0, ',', '.');
}

function twmp_revenue_shifts_render_staff_select($date, $shift, $selected, $staff_users)
{
    ob_start();
    ?>
    <select class="twmp-revenue__staff" data-revenue-input data-date="<?php echo esc_attr($date); ?>" data-shift="<?php echo esc_attr($shift); ?>" data-field="staff_user_id">
        <option value="0"><?php esc_html_e('Chọn', 'twmp-revenue-shifts'); ?></option>
        <?php foreach ($staff_users as $user) : ?>
            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected((int) $selected, (int) $user->ID); ?>>
                <?php echo esc_html($user->display_name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
    return ob_get_clean();
}

function twmp_revenue_shifts_render_money_input($date, $shift, $field, $value)
{
    ob_start();
    ?>
    <input
        class="twmp-revenue__money"
        type="text"
        inputmode="numeric"
        value="<?php echo esc_attr($value ? twmp_revenue_shifts_format_money($value) : ''); ?>"
        data-revenue-input
        data-revenue-money
        data-date="<?php echo esc_attr($date); ?>"
        data-shift="<?php echo esc_attr($shift); ?>"
        data-field="<?php echo esc_attr($field); ?>">
    <?php
    return ob_get_clean();
}

function twmp_revenue_shifts_render_readonly_value($date, $shift, $field, $value)
{
    return sprintf(
        '<output class="twmp-revenue__total" data-revenue-total data-date="%s" data-shift="%s" data-field="%s">%s</output>',
        esc_attr($date),
        esc_attr($shift),
        esc_attr($field),
        esc_html(twmp_revenue_shifts_format_money($value))
    );
}

function twmp_revenue_shifts_render_shortcode()
{
    if (!function_exists('wp_create_nonce')) {
        return '';
    }

    if (!is_user_logged_in()) {
        return '<div class="twmp-revenue"><p>' . esc_html__('Vui lòng đăng nhập để xem doanh thu.', 'twmp-revenue-shifts') . '</p></div>';
    }

    if (!twmp_revenue_shifts_user_can_view()) {
        return '<div class="twmp-revenue"><p>' . esc_html__('Tài khoản của bạn chưa được gán chi nhánh hoặc không có quyền xem doanh thu.', 'twmp-revenue-shifts') . '</p></div>';
    }

    $branch_id = twmp_revenue_shifts_get_selected_branch_id();

    if (!$branch_id || !twmp_revenue_shifts_user_can_access_branch($branch_id)) {
        return '<div class="twmp-revenue"><p>' . esc_html__('Không tìm thấy chi nhánh hợp lệ.', 'twmp-revenue-shifts') . '</p></div>';
    }

    $month = twmp_revenue_shifts_get_month();
    $start = $month . '-01';
    $days_in_month = (int) gmdate('t', strtotime($start));
    $today = current_time('Y-m-d');
    $entries = twmp_revenue_shifts_get_month_entries($branch_id, $month);
    $staff_users = twmp_revenue_shifts_get_staff_users();
    $branches = twmp_revenue_shifts_get_branch_options(false);
    $shifts = [
        'morning'   => __('SÁNG', 'twmp-revenue-shifts'),
        'afternoon' => __('CHIỀU', 'twmp-revenue-shifts'),
    ];
    $rows = [
        'staff_user_id'          => __('Người chốt', 'twmp-revenue-shifts'),
        'opening_cash'           => __('Tiền lẻ', 'twmp-revenue-shifts'),
        'cash_sales'             => __('Tiền mặt', 'twmp-revenue-shifts'),
        'exchange_cash_out'      => __('Đổi', 'twmp-revenue-shifts'),
        'expenses_cash'          => __('Chi', 'twmp-revenue-shifts'),
        'cash_actual'            => __('Tiền mặt thực tế (TMSC)', 'twmp-revenue-shifts'),
        'bank_transfer_sales'    => __('Chuyển khoản', 'twmp-revenue-shifts'),
        'bank_transfer_actual'   => __('Chuyển khoản thực tế', 'twmp-revenue-shifts'),
        'revenue_actual'         => __('Doanh thu thực tế', 'twmp-revenue-shifts'),
    ];

    wp_enqueue_style('twmp-revenue-shifts');
    wp_enqueue_script('twmp-revenue-shifts');
    wp_localize_script('twmp-revenue-shifts', 'twmpRevenueShifts', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('twmp_revenue_save_month'),
        'i18n'    => [
            'saving'    => __('Đang lưu...', 'twmp-revenue-shifts'),
            'importing' => __('Đang lấy từ đơn hàng...', 'twmp-revenue-shifts'),
            'saved'     => __('Đã lưu doanh thu.', 'twmp-revenue-shifts'),
            'error'     => __('Không thể lưu doanh thu.', 'twmp-revenue-shifts'),
        ],
    ]);

    ob_start();
    ?>
    <div class="twmp-revenue" data-revenue-root>
        <div class="twmp-revenue__inner">
            <header class="twmp-revenue__header">
                <div>
                    <p class="twmp-revenue__eyebrow"><?php esc_html_e('Doanh thu', 'twmp-revenue-shifts'); ?></p>
                    <h1 class="twmp-revenue__title"><?php echo esc_html(sprintf(__('Tháng %s', 'twmp-revenue-shifts'), gmdate('n/Y', strtotime($start)))); ?></h1>
                </div>
                <form class="twmp-revenue__filters" method="get">
                    <?php if (twmp_revenue_shifts_is_admin_user()) : ?>
                        <label>
                            <span><?php esc_html_e('Chi nhánh', 'twmp-revenue-shifts'); ?></span>
                            <select name="branch_id">
                                <?php foreach ($branches as $id => $label) : ?>
                                    <option value="<?php echo esc_attr($id); ?>" <?php selected((int) $branch_id, (int) $id); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>
                    <label>
                        <span><?php esc_html_e('Tháng', 'twmp-revenue-shifts'); ?></span>
                        <input type="month" name="revenue_month" value="<?php echo esc_attr($month); ?>">
                    </label>
                    <button type="submit"><?php esc_html_e('Xem', 'twmp-revenue-shifts'); ?></button>
                </form>
            </header>

            <form class="twmp-revenue__form" data-revenue-form>
                <input type="hidden" name="branch_id" value="<?php echo esc_attr($branch_id); ?>" data-revenue-branch>
                <input type="hidden" name="month" value="<?php echo esc_attr($month); ?>" data-revenue-month>
                <div class="twmp-revenue__status" data-revenue-status aria-live="polite"></div>
                <div class="twmp-revenue__table-wrap">
                    <table class="twmp-revenue__table">
                        <thead>
                            <tr>
                                <th class="twmp-revenue__sticky"><?php esc_html_e('Tháng', 'twmp-revenue-shifts'); ?></th>
                                <?php for ($day = 1; $day <= $days_in_month; $day++) : ?>
                                    <?php
                                    $date = sprintf('%s-%02d', $month, $day);
                                    $is_today = $date === $today;
                                    ?>
                                    <th colspan="2" class="<?php echo $is_today ? 'is-today' : ''; ?>" <?php echo $is_today ? 'data-revenue-today="1"' : ''; ?>>
                                        <?php echo esc_html($day); ?>
                                    </th>
                                <?php endfor; ?>
                            </tr>
                            <tr>
                                <th class="twmp-revenue__sticky"><?php esc_html_e('Doanh thu', 'twmp-revenue-shifts'); ?></th>
                                <?php for ($day = 1; $day <= $days_in_month; $day++) : ?>
                                    <?php
                                    $date = sprintf('%s-%02d', $month, $day);
                                    $is_today = $date === $today;
                                    foreach ($shifts as $shift => $shift_label) :
                                        ?>
                                        <th class="<?php echo $is_today ? 'is-today' : ''; ?>" data-date="<?php echo esc_attr($date); ?>" data-shift="<?php echo esc_attr($shift); ?>">
                                            <?php echo esc_html($shift_label); ?>
                                        </th>
                                    <?php endforeach; ?>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $field => $label) : ?>
                                <tr>
                                    <th class="twmp-revenue__sticky"><?php echo esc_html($label); ?></th>
                                    <?php for ($day = 1; $day <= $days_in_month; $day++) : ?>
                                        <?php
                                        $date = sprintf('%s-%02d', $month, $day);
                                        foreach ($shifts as $shift => $shift_label) :
                                            $entry = isset($entries[$date][$shift]) ? $entries[$date][$shift] : [];
                                            $calc = twmp_revenue_shifts_calculate($entry);
                                            $value = isset($entry[$field]) ? $entry[$field] : (isset($calc[$field]) ? $calc[$field] : 0);
                                            ?>
                                            <td class="<?php echo $date === $today ? 'is-today' : ''; ?>" data-date="<?php echo esc_attr($date); ?>" data-shift="<?php echo esc_attr($shift); ?>">
                                                <?php
                                                if ('staff_user_id' === $field) {
                                                    echo twmp_revenue_shifts_render_staff_select($date, $shift, $value ? $value : get_current_user_id(), $staff_users); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                } elseif (in_array($field, twmp_revenue_shifts_money_fields(), true)) {
                                                    echo twmp_revenue_shifts_render_money_input($date, $shift, $field, $value); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                } else {
                                                    echo twmp_revenue_shifts_render_readonly_value($date, $shift, $field, $value); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="twmp-revenue__actions">
                    <button type="button" class="twmp-revenue__import" data-revenue-import><?php esc_html_e('Lấy từ đơn hàng', 'twmp-revenue-shifts'); ?></button>
                    <button type="submit" class="twmp-revenue__save"><?php esc_html_e('Lưu doanh thu', 'twmp-revenue-shifts'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function twmp_revenue_shifts_ajax_save_month()
{
    check_ajax_referer('twmp_revenue_save_month', 'nonce');

    if (!twmp_revenue_shifts_user_can_view()) {
        wp_send_json_error(['message' => __('Bạn không có quyền lưu doanh thu.', 'twmp-revenue-shifts')], 403);
    }

    $branch_id = isset($_POST['branch_id']) ? absint(wp_unslash($_POST['branch_id'])) : 0;
    $month = isset($_POST['month']) ? sanitize_text_field(wp_unslash($_POST['month'])) : '';
    $entries_json = isset($_POST['entries']) ? wp_unslash($_POST['entries']) : '';

    if (!$branch_id || !twmp_revenue_shifts_user_can_access_branch($branch_id)) {
        wp_send_json_error(['message' => __('Chi nhánh không hợp lệ.', 'twmp-revenue-shifts')], 400);
    }

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        wp_send_json_error(['message' => __('Tháng không hợp lệ.', 'twmp-revenue-shifts')], 400);
    }

    $entries = json_decode($entries_json, true);

    if (!is_array($entries)) {
        wp_send_json_error(['message' => __('Dữ liệu không hợp lệ.', 'twmp-revenue-shifts')], 400);
    }

    $saved = twmp_revenue_shifts_save_entries($branch_id, $month, $entries);

    wp_send_json_success([
        'saved'   => $saved,
        'message' => __('Đã lưu doanh thu.', 'twmp-revenue-shifts'),
    ]);
}

function twmp_revenue_shifts_ajax_import_orders()
{
    check_ajax_referer('twmp_revenue_save_month', 'nonce');

    if (!twmp_revenue_shifts_user_can_view()) {
        wp_send_json_error(['message' => __('Bạn không có quyền lấy doanh thu từ đơn hàng.', 'twmp-revenue-shifts')], 403);
    }

    if (!function_exists('wc_get_orders')) {
        wp_send_json_error(['message' => __('WooCommerce chưa sẵn sàng.', 'twmp-revenue-shifts')], 400);
    }

    $branch_id = isset($_POST['branch_id']) ? absint(wp_unslash($_POST['branch_id'])) : 0;
    $month = isset($_POST['month']) ? sanitize_text_field(wp_unslash($_POST['month'])) : '';

    if (!$branch_id || !twmp_revenue_shifts_user_can_access_branch($branch_id)) {
        wp_send_json_error(['message' => __('Chi nhánh không hợp lệ.', 'twmp-revenue-shifts')], 400);
    }

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        wp_send_json_error(['message' => __('Tháng không hợp lệ.', 'twmp-revenue-shifts')], 400);
    }

    $result = twmp_revenue_shifts_import_orders($branch_id, $month);

    wp_send_json_success([
        'entries' => $result['entries'],
        'summary' => $result['summary'],
        'message' => twmp_revenue_shifts_get_import_message($result['summary']),
    ]);
}

function twmp_revenue_shifts_get_import_message($summary)
{
    return sprintf(
        /* translators: 1: order count, 2: cash total, 3: bank total, 4: skipped order count */
        __('Đã lấy %1$d đơn. Tiền mặt: %2$s. Chuyển khoản: %3$s. Bỏ qua: %4$d đơn.', 'twmp-revenue-shifts'),
        isset($summary['orders_count']) ? (int) $summary['orders_count'] : 0,
        twmp_revenue_shifts_format_money(isset($summary['cash_total']) ? $summary['cash_total'] : 0),
        twmp_revenue_shifts_format_money(isset($summary['bank_total']) ? $summary['bank_total'] : 0),
        isset($summary['skipped_orders_count']) ? (int) $summary['skipped_orders_count'] : 0
    );
}

function twmp_revenue_shifts_import_orders($branch_id, $month)
{
    $branch_id = absint($branch_id);
    $range = twmp_revenue_shifts_get_month_range($month);
    $orders = twmp_revenue_shifts_get_orders_for_import($branch_id, $range['start'], $range['end']);
    $cash_methods = apply_filters('twmp_revenue_shifts_cash_payment_methods', ['cod']);
    $bank_methods = apply_filters('twmp_revenue_shifts_bank_payment_methods', ['bacs']);
    $entries = [];
    $summary = [
        'orders_count' => 0,
        'cash_orders_count' => 0,
        'bank_orders_count' => 0,
        'skipped_orders_count' => 0,
        'cash_total' => 0,
        'bank_total' => 0,
    ];

    foreach ($orders as $order) {
        if (!$order instanceof WC_Order) {
            continue;
        }

        $date = $order->get_date_created();

        if (!$date instanceof WC_DateTime) {
            continue;
        }

        $business_date = $date->date_i18n('Y-m-d');
        $shift_key = twmp_revenue_shifts_get_shift_key_for_datetime($date);

        if (!isset($entries[$business_date])) {
            $entries[$business_date] = [];
        }

        if (!isset($entries[$business_date][$shift_key])) {
            $entries[$business_date][$shift_key] = [
                'cash_sales' => 0,
                'bank_transfer_sales' => 0,
            ];
        }

        $payment_method = (string) $order->get_payment_method();
        $total = (int) round((float) $order->get_total());
        $summary['orders_count']++;

        if (in_array($payment_method, $cash_methods, true)) {
            $entries[$business_date][$shift_key]['cash_sales'] += $total;
            $summary['cash_orders_count']++;
            $summary['cash_total'] += $total;
            continue;
        }

        if (in_array($payment_method, $bank_methods, true)) {
            $entries[$business_date][$shift_key]['bank_transfer_sales'] += $total;
            $summary['bank_orders_count']++;
            $summary['bank_total'] += $total;
            continue;
        }

        $summary['skipped_orders_count']++;
    }

    return [
        'entries' => $entries,
        'summary' => $summary,
    ];
}

function twmp_revenue_shifts_get_orders_for_import($branch_id, $start, $end)
{
    $statuses = apply_filters('twmp_revenue_shifts_import_order_statuses', ['on-hold', 'processing', 'completed']);
    $branch_meta_key = defined('TWMP_STAFF_ORDERS_ORDER_BRANCH_META') ? TWMP_STAFF_ORDERS_ORDER_BRANCH_META : '_twmp_branch_id';

    return wc_get_orders([
        'limit' => -1,
        'return' => 'objects',
        'status' => $statuses,
        'orderby' => 'date',
        'order' => 'ASC',
        'date_created' => $start->getTimestamp() . '...' . $end->getTimestamp(),
        'meta_query' => [
            [
                'key' => $branch_meta_key,
                'value' => (string) absint($branch_id),
                'compare' => '=',
            ],
        ],
    ]);
}

function twmp_revenue_shifts_get_month_range($month)
{
    $timezone = wp_timezone();
    $start = new DateTimeImmutable($month . '-01 00:00:00', $timezone);
    $end = $start->modify('last day of this month')->setTime(23, 59, 59);

    return [
        'start' => $start,
        'end' => $end,
    ];
}

function twmp_revenue_shifts_get_shift_key_for_datetime($date)
{
    if ($date instanceof WC_DateTime) {
        $hour = (int) $date->date_i18n('G');
        $minute = (int) $date->date_i18n('i');
    } elseif ($date instanceof DateTimeInterface) {
        $hour = (int) wp_date('G', $date->getTimestamp());
        $minute = (int) wp_date('i', $date->getTimestamp());
    } else {
        return 'morning';
    }

    $afternoon_starts_at = apply_filters('twmp_revenue_shifts_afternoon_starts_at', twmp_revenue_shifts_get_afternoon_starts_at());
    $parts = explode(':', (string) $afternoon_starts_at);
    $start_hour = isset($parts[0]) ? max(0, min(23, absint($parts[0]))) : 15;
    $start_minute = isset($parts[1]) ? max(0, min(59, absint($parts[1]))) : 0;

    if ($hour > $start_hour || ($hour === $start_hour && $minute >= $start_minute)) {
        return 'afternoon';
    }

    return 'morning';
}

function twmp_revenue_shifts_get_afternoon_starts_at()
{
    $time = '';

    if (function_exists('get_field')) {
        $time = (string) get_field('twmp_revenue_afternoon_starts_at', 'option');
    }

    if (!preg_match('/^\d{1,2}:\d{2}$/', $time)) {
        return '15:00';
    }

    return $time;
}

function twmp_revenue_shifts_save_entries($branch_id, $month, $entries)
{
    global $wpdb;

    $branch_id = absint($branch_id);
    $table = twmp_revenue_shifts_table();
    $saved = 0;
    $valid_shifts = ['morning', 'afternoon'];
    $money_fields = twmp_revenue_shifts_money_fields();
    $now = current_time('mysql');

    foreach ($entries as $date => $shift_entries) {
        if (!is_array($shift_entries) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) || 0 !== strpos((string) $date, $month . '-')) {
            continue;
        }

        foreach ($shift_entries as $shift_key => $entry) {
            if (!in_array($shift_key, $valid_shifts, true) || !is_array($entry)) {
                continue;
            }

            $data = [
                'branch_id'            => $branch_id,
                'business_date'        => $date,
                'shift_key'            => $shift_key,
                'staff_user_id'        => isset($entry['staff_user_id']) ? absint($entry['staff_user_id']) : get_current_user_id(),
                'opening_cash'         => 0,
                'cash_sales'           => 0,
                'exchange_cash_out'    => 0,
                'expenses_cash'        => 0,
                'bank_transfer_sales'  => 0,
                'status'               => 'draft',
                'updated_at'           => $now,
            ];

            foreach ($money_fields as $field) {
                $data[$field] = isset($entry[$field]) ? twmp_revenue_shifts_parse_money($entry[$field]) : 0;
            }

            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE branch_id = %d AND business_date = %s AND shift_key = %s LIMIT 1",
                    $branch_id,
                    $date,
                    $shift_key
                )
            );

            if ($existing_id) {
                $wpdb->update(
                    $table,
                    $data,
                    ['id' => absint($existing_id)],
                    ['%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s'],
                    ['%d']
                );
            } else {
                $data['created_at'] = $now;
                $wpdb->insert(
                    $table,
                    $data,
                    ['%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s']
                );
            }

            $saved++;
        }
    }

    return $saved;
}
