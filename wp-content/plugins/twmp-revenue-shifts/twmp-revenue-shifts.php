<?php
/**
 * Plugin Name: TWMP Revenue Shifts
 * Description: Shift-based cafe revenue tracking for OrderCafe.
 * Version: 0.1.14
 * Author: TWMP
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TWMP_REVENUE_SHIFTS_VERSION', '0.1.14');
define('TWMP_REVENUE_SHIFTS_FILE', __FILE__);
define('TWMP_REVENUE_SHIFTS_DIR', plugin_dir_path(__FILE__));
define('TWMP_REVENUE_SHIFTS_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, 'twmp_revenue_shifts_activate');

add_action('wp_enqueue_scripts', 'twmp_revenue_shifts_register_assets');
add_action('admin_enqueue_scripts', 'twmp_revenue_shifts_register_assets');
add_action('init', 'twmp_revenue_shifts_maybe_upgrade');
add_shortcode('twmp_revenue', 'twmp_revenue_shifts_render_shortcode');
add_action('rest_api_init', 'twmp_revenue_shifts_register_rest_routes');
add_action('admin_post_twmp_revenue_export_month', 'twmp_revenue_shifts_export_month');
add_action('admin_post_twmp_revenue_clear_all', 'twmp_revenue_shifts_clear_all_data');
add_action('woocommerce_order_status_changed', 'twmp_revenue_shifts_maybe_sync_order_revenue', 20, 4);
add_action('woocommerce_before_delete_order', 'twmp_revenue_shifts_sync_order_revenue_from_hook', 20, 2);
add_action('woocommerce_before_trash_order', 'twmp_revenue_shifts_sync_order_revenue_from_hook', 20, 2);
add_action('woocommerce_untrash_order', 'twmp_revenue_shifts_sync_order_revenue_from_hook', 20, 1);

function twmp_revenue_shifts_register_rest_routes()
{
    register_rest_route('twmp-revenue-shifts/v1', '/month', [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => 'twmp_revenue_shifts_rest_save_month',
        'permission_callback' => 'twmp_revenue_shifts_user_can_view',
    ]);

    register_rest_route('twmp-revenue-shifts/v1', '/import-orders', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'twmp_revenue_shifts_rest_import_orders',
        'permission_callback' => 'twmp_revenue_shifts_user_can_view',
    ]);
}

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
        failed_sales bigint(20) NOT NULL DEFAULT 0,
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

function twmp_revenue_shifts_maybe_upgrade()
{
    if (get_option('twmp_revenue_shifts_db_version') === TWMP_REVENUE_SHIFTS_VERSION) {
        return;
    }

    twmp_revenue_shifts_create_tables();
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

function twmp_revenue_shifts_get_selected_date($month = '')
{
    $requested = isset($_GET['revenue_date']) ? sanitize_text_field(wp_unslash($_GET['revenue_date'])) : '';

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested)) {
        return $requested;
    }

    if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
        return $month === current_time('Y-m') ? current_time('Y-m-d') : $month . '-01';
    }

    return current_time('Y-m-d');
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

function twmp_revenue_shifts_get_selected_staff_user_id()
{
    if (!twmp_revenue_shifts_is_admin_user()) {
        return get_current_user_id();
    }

    return isset($_GET['staff_user_id']) ? absint(wp_unslash($_GET['staff_user_id'])) : 0;
}

function twmp_revenue_shifts_filter_entries_by_staff_user($entries, $staff_user_id)
{
    $staff_user_id = absint($staff_user_id);

    if (!$staff_user_id) {
        return $entries;
    }

    $filtered = [];

    foreach ((array) $entries as $date => $shift_entries) {
        foreach ((array) $shift_entries as $shift_key => $entry) {
            if (absint(isset($entry['staff_user_id']) ? $entry['staff_user_id'] : 0) !== $staff_user_id) {
                continue;
            }

            $filtered[$date][$shift_key] = $entry;
        }
    }

    return $filtered;
}

function twmp_revenue_shifts_money_fields()
{
    return [
        'opening_cash',
        'cash_sales',
        'exchange_cash_out',
        'expenses_cash',
        'bank_transfer_sales',
        'failed_sales',
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
    $cash_sales = isset($row['cash_sales']) ? (int) $row['cash_sales'] : 0;
    $expenses_cash = isset($row['expenses_cash']) ? (int) $row['expenses_cash'] : 0;
    $bank_transfer_sales = isset($row['bank_transfer_sales']) ? (int) $row['bank_transfer_sales'] : 0;

    return [
        'revenue_actual' => $cash_sales + $bank_transfer_sales - $expenses_cash,
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

function twmp_revenue_shifts_get_period_totals($entries)
{
    $totals = [
        'cash_sales'          => 0,
        'bank_transfer_sales' => 0,
        'failed_sales'        => 0,
        'expenses_cash'       => 0,
        'revenue_actual'      => 0,
    ];

    foreach ((array) $entries as $shift_entries) {
        foreach ((array) $shift_entries as $entry) {
            $calc = twmp_revenue_shifts_calculate($entry);

            $totals['cash_sales'] += isset($entry['cash_sales']) ? (int) $entry['cash_sales'] : 0;
            $totals['bank_transfer_sales'] += isset($entry['bank_transfer_sales']) ? (int) $entry['bank_transfer_sales'] : 0;
            $totals['failed_sales'] += isset($entry['failed_sales']) ? (int) $entry['failed_sales'] : 0;
            $totals['expenses_cash'] += isset($entry['expenses_cash']) ? (int) $entry['expenses_cash'] : 0;
            $totals['revenue_actual'] += isset($calc['revenue_actual']) ? (int) $calc['revenue_actual'] : 0;
        }
    }

    return $totals;
}

function twmp_revenue_shifts_get_export_url($branch_id, $month)
{
    $branch_id = absint($branch_id);
    $month = sanitize_text_field($month);
    $url = add_query_arg(
        [
            'action'        => 'twmp_revenue_export_month',
            'branch_id'     => $branch_id,
            'revenue_month' => $month,
        ],
        admin_url('admin-post.php')
    );

    return wp_nonce_url($url, 'twmp_revenue_export_month_' . $branch_id . '_' . $month);
}

function twmp_revenue_shifts_get_clear_all_url()
{
    return admin_url('admin-post.php');
}

function twmp_revenue_shifts_render_staff_select($date, $shift, $selected, $staff_users)
{
    if (!twmp_revenue_shifts_is_admin_user()) {
        $user = wp_get_current_user();
        $user_id = $user instanceof WP_User ? (int) $user->ID : get_current_user_id();
        $display_name = $user instanceof WP_User ? $user->display_name : '';

        return sprintf(
            '<span class="twmp-revenue__staff-name">%s</span><input type="hidden" data-revenue-input data-date="%s" data-shift="%s" data-field="staff_user_id" value="%d">',
            esc_html($display_name),
            esc_attr($date),
            esc_attr($shift),
            $user_id
        );
    }

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

function twmp_revenue_shifts_render_money_input($date, $shift, $field, $value, $readonly = false)
{
    ob_start();
    ?>
    <input
        class="twmp-revenue__money<?php echo $readonly ? ' is-readonly' : ''; ?>"
        type="text"
        inputmode="numeric"
        value="<?php echo esc_attr($value ? twmp_revenue_shifts_format_money($value) : ''); ?>"
        data-revenue-input
        data-revenue-money
        data-date="<?php echo esc_attr($date); ?>"
        data-shift="<?php echo esc_attr($shift); ?>"
        data-field="<?php echo esc_attr($field); ?>"
        <?php echo $readonly ? 'readonly="readonly"' : ''; ?>>
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

function twmp_revenue_shifts_render_access_notice($args = [])
{
    if (function_exists('twmp_render_access_notice')) {
        return twmp_render_access_notice($args);
    }

    $defaults = [
        'title'   => __('Bạn không có quyền truy cập', 'twmp-revenue-shifts'),
        'message' => __('Tài khoản của bạn chưa được cấp quyền phù hợp hoặc chưa được gán chi nhánh.', 'twmp-revenue-shifts'),
    ];
    $args = wp_parse_args($args, $defaults);

    return sprintf(
        '<div class="twmp-revenue"><div class="twmp-access-notice"><div class="twmp-access-notice__body"><h2 class="twmp-access-notice__title">%s</h2><p class="twmp-access-notice__message">%s</p></div></div></div>',
        esc_html($args['title']),
        esc_html($args['message'])
    );
}

function twmp_revenue_shifts_render_shortcode()
{
    if (!function_exists('wp_create_nonce')) {
        return '';
    }

    if (!is_user_logged_in()) {
        return twmp_revenue_shifts_render_access_notice([
            'type'        => 'login',
            'title'       => __('Vui lòng đăng nhập', 'twmp-revenue-shifts'),
            'message'     => __('Bạn cần đăng nhập bằng tài khoản được cấp quyền để xem khu vực này.', 'twmp-revenue-shifts'),
            'action_url'  => wp_login_url(get_permalink()),
            'action_text' => __('Đăng nhập', 'twmp-revenue-shifts'),
        ]);
    }

    if (!twmp_revenue_shifts_user_can_view()) {
        return twmp_revenue_shifts_render_access_notice([
            'title'   => __('Bạn không có quyền truy cập', 'twmp-revenue-shifts'),
            'message' => __('Tài khoản của bạn chưa được cấp quyền xem doanh thu hoặc chưa được gán chi nhánh.', 'twmp-revenue-shifts'),
        ]);
    }

    $branch_id = twmp_revenue_shifts_get_selected_branch_id();

    if (!$branch_id || !twmp_revenue_shifts_user_can_access_branch($branch_id)) {
        return twmp_revenue_shifts_render_access_notice([
            'title'   => __('Không tìm thấy chi nhánh hợp lệ', 'twmp-revenue-shifts'),
            'message' => __('Tài khoản của bạn chưa được gán chi nhánh hợp lệ để xem dữ liệu doanh thu.', 'twmp-revenue-shifts'),
        ]);
    }

    $today = twmp_revenue_shifts_is_admin_user() ? twmp_revenue_shifts_get_selected_date() : current_time('Y-m-d');
    $month = substr($today, 0, 7);
    $is_cleared = !empty($_GET['twmp_revenue_cleared']);

    $entries = twmp_revenue_shifts_get_month_entries($branch_id, $month);
    $staff_users = twmp_revenue_shifts_get_staff_users();
    $selected_staff_user_id = twmp_revenue_shifts_get_selected_staff_user_id();
    $entries = twmp_revenue_shifts_filter_entries_by_staff_user($entries, $selected_staff_user_id);

    if ($is_cleared) {
        $entries = [];
    }

    $branches = twmp_revenue_shifts_get_branch_options(false);
    $shifts = [
        'morning'   => __('SÁNG', 'twmp-revenue-shifts'),
        'afternoon' => __('CHIỀU', 'twmp-revenue-shifts'),
    ];
    $rows = [
        'cash_sales'          => __('Tiền mặt', 'twmp-revenue-shifts'),
        'bank_transfer_sales' => __('Chuyển khoản', 'twmp-revenue-shifts'),
        'failed_sales'        => __('Đơn lỗi', 'twmp-revenue-shifts'),
        'expenses_cash'       => __('Chi', 'twmp-revenue-shifts'),
        'revenue_actual'      => __('Doanh thu', 'twmp-revenue-shifts'),
    ];
    $summary_rows = [
        'cash_sales'          => __('Tiền mặt', 'twmp-revenue-shifts'),
        'bank_transfer_sales' => __('Chuyển khoản', 'twmp-revenue-shifts'),
        'failed_sales'        => __('Đơn lỗi', 'twmp-revenue-shifts'),
        'expenses_cash'       => __('Chi', 'twmp-revenue-shifts'),
        'revenue_actual'      => __('Doanh thu', 'twmp-revenue-shifts'),
    ];
    $summary_totals = twmp_revenue_shifts_get_period_totals(isset($entries[$today]) ? [$today => $entries[$today]] : []);
    $month_totals = twmp_revenue_shifts_get_period_totals($entries);

    wp_enqueue_style('twmp-revenue-shifts');
    wp_enqueue_script('twmp-revenue-shifts');
    wp_localize_script('twmp-revenue-shifts', 'twmpRevenueShifts', [
        'restUrl' => esc_url_raw(rest_url('twmp-revenue-shifts/v1')),
        'nonce'   => wp_create_nonce('wp_rest'),
        'autoImport' => !$is_cleared,
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
                    <h1 class="twmp-revenue__title"><?php echo esc_html(sprintf(__('Ngày %s', 'twmp-revenue-shifts'), wp_date('d/m/Y', strtotime($today)))); ?></h1>
                    <p class="twmp-revenue__branch"><?php echo esc_html(isset($branches[$branch_id]) ? $branches[$branch_id] : ''); ?></p>
                </div>
                <?php if (twmp_revenue_shifts_is_admin_user()) : ?>
                    <form class="twmp-revenue__filters" method="get" data-revenue-filters>
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
                        <label>
                            <span><?php esc_html_e('Ngày', 'twmp-revenue-shifts'); ?></span>
                            <input type="date" name="revenue_date" value="<?php echo esc_attr($today); ?>">
                        </label>
                        <label>
                            <span><?php esc_html_e('Nhân viên', 'twmp-revenue-shifts'); ?></span>
                            <select name="staff_user_id" data-revenue-filter-staff>
                                <option value="0"><?php esc_html_e('Tất cả', 'twmp-revenue-shifts'); ?></option>
                                <?php foreach ($staff_users as $user) : ?>
                                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected((int) $selected_staff_user_id, (int) $user->ID); ?>>
                                        <?php echo esc_html($user->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit"><?php esc_html_e('Xem', 'twmp-revenue-shifts'); ?></button>
                        <a class="twmp-revenue__export" href="<?php echo esc_url(twmp_revenue_shifts_get_export_url($branch_id, $month)); ?>">
                            <?php esc_html_e('Export CSV', 'twmp-revenue-shifts'); ?>
                        </a>
                    </form>
                    <form class="twmp-revenue__clear-form" method="post" action="<?php echo esc_url(twmp_revenue_shifts_get_clear_all_url()); ?>" data-revenue-clear-form>
                        <?php wp_nonce_field('twmp_revenue_clear_all'); ?>
                        <input type="hidden" name="action" value="twmp_revenue_clear_all">
                        <input type="hidden" name="redirect_url" value="<?php echo esc_url(remove_query_arg('twmp_revenue_cleared')); ?>">
                        <button type="submit" class="twmp-revenue__clear"><?php esc_html_e('Xoa data va don hang', 'twmp-revenue-shifts'); ?></button>
                    </form>
                <?php endif; ?>
            </header>

            <div class="twmp-revenue__summary" aria-label="<?php esc_attr_e('Tổng doanh thu hôm nay', 'twmp-revenue-shifts'); ?>">
                <?php foreach ($summary_rows as $field => $label) : ?>
                    <div class="twmp-revenue__metric twmp-revenue__metric--<?php echo esc_attr($field); ?>">
                        <span class="twmp-revenue__metric-label"><?php echo esc_html($label); ?></span>
                        <strong class="twmp-revenue__metric-value" data-revenue-day-total="<?php echo esc_attr($field); ?>">
                            <?php echo esc_html(twmp_revenue_shifts_format_money($summary_totals[$field])); ?>
                        </strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (twmp_revenue_shifts_is_admin_user()) : ?>
                <div class="twmp-revenue__month-summary" aria-label="<?php esc_attr_e('Tổng doanh thu tháng', 'twmp-revenue-shifts'); ?>">
                    <strong><?php echo esc_html(sprintf(__('Tổng tháng %s', 'twmp-revenue-shifts'), wp_date('m/Y', strtotime($month . '-01')))); ?></strong>
                    <?php foreach ($summary_rows as $field => $label) : ?>
                        <span>
                            <?php echo esc_html($label); ?>:
                            <b><?php echo esc_html(twmp_revenue_shifts_format_money($month_totals[$field])); ?></b>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="twmp-revenue__form" data-revenue-form>
                <input type="hidden" name="branch_id" value="<?php echo esc_attr($branch_id); ?>" data-revenue-branch>
                <input type="hidden" name="month" value="<?php echo esc_attr($month); ?>" data-revenue-month>
                <input type="hidden" name="date" value="<?php echo esc_attr($today); ?>" data-revenue-date>
                <div class="twmp-revenue__status" data-revenue-status aria-live="polite"></div>
                <div class="twmp-revenue__table-wrap">
                    <table class="twmp-revenue__table">
                        <thead>
                            <tr>
                                <th class="twmp-revenue__sticky"><?php esc_html_e('', 'twmp-revenue-shifts'); ?></th>
                                <?php foreach ($shifts as $shift => $shift_label) : ?>
                                    <th class="is-today" data-date="<?php echo esc_attr($today); ?>" data-shift="<?php echo esc_attr($shift); ?>" data-revenue-today="1">
                                        <?php echo esc_html($shift_label); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $field => $label) : ?>
                                <tr>
                                    <th class="twmp-revenue__sticky"><?php echo esc_html($label); ?></th>
                                    <?php foreach ($shifts as $shift => $shift_label) : ?>
                                        <?php
                                            $date = $today;
                                            $entry = isset($entries[$date][$shift]) ? $entries[$date][$shift] : [];
                                            $calc = twmp_revenue_shifts_calculate($entry);
                                            $value = isset($entry[$field]) ? $entry[$field] : (isset($calc[$field]) ? $calc[$field] : 0);
                                            ?>
                                            <td class="is-today" data-date="<?php echo esc_attr($date); ?>" data-shift="<?php echo esc_attr($shift); ?>">
                                                <?php
                                                if ('staff_user_id' === $field) {
                                                    echo twmp_revenue_shifts_render_staff_select($date, $shift, $value ? $value : $selected_staff_user_id, $staff_users); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                } elseif (in_array($field, twmp_revenue_shifts_money_fields(), true)) {
                                                    $is_readonly_money = in_array($field, ['cash_sales', 'bank_transfer_sales', 'failed_sales'], true);
                                                    echo twmp_revenue_shifts_render_money_input($date, $shift, $field, $value, $is_readonly_money); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                } else {
                                                    echo twmp_revenue_shifts_render_readonly_value($date, $shift, $field, $value); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                }
                                                ?>
                                            </td>
                                    <?php endforeach; ?>
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

function twmp_revenue_shifts_export_month()
{
    if (!twmp_revenue_shifts_is_admin_user()) {
        wp_die(esc_html__('Only administrators can export revenue.', 'twmp-revenue-shifts'), '', ['response' => 403]);
    }

    $branch_id = isset($_GET['branch_id']) ? absint(wp_unslash($_GET['branch_id'])) : 0;
    $month = isset($_GET['revenue_month']) ? sanitize_text_field(wp_unslash($_GET['revenue_month'])) : '';

    if (!$branch_id || !twmp_revenue_shifts_user_can_access_branch($branch_id)) {
        wp_die(esc_html__('Invalid branch.', 'twmp-revenue-shifts'), '', ['response' => 400]);
    }

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        wp_die(esc_html__('Invalid month.', 'twmp-revenue-shifts'), '', ['response' => 400]);
    }

    check_admin_referer('twmp_revenue_export_month_' . $branch_id . '_' . $month);

    $entries = twmp_revenue_shifts_get_month_entries($branch_id, $month);
    $branches = twmp_revenue_shifts_get_branch_options(false);
    $branch_name = isset($branches[$branch_id]) ? $branches[$branch_id] : ('Branch ' . $branch_id);
    $filename = sanitize_file_name('revenue-' . $month . '-branch-' . $branch_id . '.csv');
    $shifts = [
        'morning'   => 'Sáng',
        'afternoon' => 'Chiều',
    ];
    $totals = twmp_revenue_shifts_get_period_totals($entries);
    $range = twmp_revenue_shifts_get_month_range($month);

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if (!$output) {
        exit;
    }

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Chi nhánh', $branch_name]);
    fputcsv($output, ['Tháng', $month]);
    fputcsv($output, []);
    fputcsv($output, ['Ngày', 'Ca', 'Người chốt', 'Tiền mặt', 'Chuyển khoản', 'Đơn lỗi', 'Chi', 'Doanh thu']);

    for ($date = $range['start']; $date <= $range['end']; $date = $date->modify('+1 day')) {
        $business_date = $date->format('Y-m-d');

        foreach ($shifts as $shift_key => $shift_label) {
            $entry = isset($entries[$business_date][$shift_key]) ? $entries[$business_date][$shift_key] : [];
            $calc = twmp_revenue_shifts_calculate($entry);
            $staff_user_id = isset($entry['staff_user_id']) ? absint($entry['staff_user_id']) : 0;
            $staff = $staff_user_id ? get_userdata($staff_user_id) : false;

            fputcsv($output, [
                $business_date,
                $shift_label,
                $staff instanceof WP_User ? $staff->display_name : '',
                isset($entry['cash_sales']) ? (int) $entry['cash_sales'] : 0,
                isset($entry['bank_transfer_sales']) ? (int) $entry['bank_transfer_sales'] : 0,
                isset($entry['failed_sales']) ? (int) $entry['failed_sales'] : 0,
                isset($entry['expenses_cash']) ? (int) $entry['expenses_cash'] : 0,
                isset($calc['revenue_actual']) ? (int) $calc['revenue_actual'] : 0,
            ]);
        }
    }

    fputcsv($output, []);
    fputcsv($output, [
        'Tổng',
        '',
        '',
        $totals['cash_sales'],
        $totals['bank_transfer_sales'],
        $totals['failed_sales'],
        $totals['expenses_cash'],
        $totals['revenue_actual'],
    ]);

    fclose($output);
    exit;
}

function twmp_revenue_shifts_clear_all_data()
{
    if (!twmp_revenue_shifts_is_admin_user()) {
        wp_die(esc_html__('Only administrators can clear revenue data.', 'twmp-revenue-shifts'), '', ['response' => 403]);
    }

    check_admin_referer('twmp_revenue_clear_all');

    global $wpdb;

    $deleted_orders = twmp_revenue_shifts_delete_all_orders();
    $table = twmp_revenue_shifts_table();
    $wpdb->query("DELETE FROM {$table}");

    $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw(wp_unslash($_POST['redirect_url'])) : '';

    if (!$redirect_url) {
        $page_id = absint(get_option('twmp_revenue_shifts_page_id', 0));
        $redirect_url = $page_id ? get_permalink($page_id) : home_url('/');
    }

    wp_safe_redirect(
        add_query_arg(
            [
                'twmp_revenue_cleared' => '1',
                'twmp_orders_deleted'  => $deleted_orders,
            ],
            $redirect_url
        )
    );
    exit;
}

function twmp_revenue_shifts_delete_all_orders()
{
    if (!function_exists('wc_get_orders')) {
        return 0;
    }

    $order_ids = wc_get_orders([
        'limit'  => -1,
        'return' => 'ids',
        'type'   => 'shop_order',
        'status' => array_merge(array_keys(wc_get_order_statuses()), ['trash']),
    ]);

    $deleted = 0;

    foreach ((array) $order_ids as $order_id) {
        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order) {
            continue;
        }

        $order->delete(true);
        $deleted++;
    }

    return $deleted;
}


function twmp_revenue_shifts_rest_save_month(WP_REST_Request $request)
{
    if (!twmp_revenue_shifts_user_can_view()) {
        return new WP_Error('twmp_revenue_forbidden', __('You cannot save revenue.', 'twmp-revenue-shifts'), ['status' => 403]);
    }

    $branch_id = absint($request->get_param('branch_id'));
    $month = sanitize_text_field((string) $request->get_param('month'));
    $entries = $request->get_param('entries');

    if (!$branch_id || !twmp_revenue_shifts_user_can_access_branch($branch_id)) {
        return new WP_Error('twmp_revenue_invalid_branch', __('Invalid branch.', 'twmp-revenue-shifts'), ['status' => 400]);
    }

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        return new WP_Error('twmp_revenue_invalid_month', __('Invalid month.', 'twmp-revenue-shifts'), ['status' => 400]);
    }

    if (is_string($entries)) {
        $entries = json_decode($entries, true);
    }

    if (!is_array($entries)) {
        return new WP_Error('twmp_revenue_invalid_entries', __('Invalid revenue data.', 'twmp-revenue-shifts'), ['status' => 400]);
    }

    $saved = twmp_revenue_shifts_save_entries($branch_id, $month, $entries, ['expenses_cash']);

    return rest_ensure_response([
        'saved'   => $saved,
        'message' => __('Revenue saved.', 'twmp-revenue-shifts'),
    ]);
}

function twmp_revenue_shifts_rest_import_orders(WP_REST_Request $request)
{
    if (!twmp_revenue_shifts_user_can_view()) {
        return new WP_Error('twmp_revenue_forbidden', __('You cannot import order revenue.', 'twmp-revenue-shifts'), ['status' => 403]);
    }

    if (!function_exists('wc_get_orders')) {
        return new WP_Error('twmp_revenue_woocommerce_missing', __('WooCommerce is not ready.', 'twmp-revenue-shifts'), ['status' => 400]);
    }

    $branch_id = absint($request->get_param('branch_id'));
    $month = sanitize_text_field((string) $request->get_param('month'));
    $date = sanitize_text_field((string) ($request->get_param('date') ?: current_time('Y-m-d')));
    $staff_user_id = twmp_revenue_shifts_is_admin_user() ? absint($request->get_param('staff_user_id')) : get_current_user_id();

    if (!$branch_id || !twmp_revenue_shifts_user_can_access_branch($branch_id)) {
        return new WP_Error('twmp_revenue_invalid_branch', __('Invalid branch.', 'twmp-revenue-shifts'), ['status' => 400]);
    }

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        return new WP_Error('twmp_revenue_invalid_month', __('Invalid month.', 'twmp-revenue-shifts'), ['status' => 400]);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || 0 !== strpos($date, $month . '-')) {
        return new WP_Error('twmp_revenue_invalid_date', __('Invalid date.', 'twmp-revenue-shifts'), ['status' => 400]);
    }

    $result = twmp_revenue_shifts_import_orders($branch_id, $month, $date, array(), $staff_user_id);

    foreach (['morning', 'afternoon'] as $shift_key) {
        if (!isset($result['entries'][$date][$shift_key])) {
            $result['entries'][$date][$shift_key] = [];
        }

        $result['entries'][$date][$shift_key]['cash_sales'] = isset($result['entries'][$date][$shift_key]['cash_sales']) ? $result['entries'][$date][$shift_key]['cash_sales'] : 0;
        $result['entries'][$date][$shift_key]['bank_transfer_sales'] = isset($result['entries'][$date][$shift_key]['bank_transfer_sales']) ? $result['entries'][$date][$shift_key]['bank_transfer_sales'] : 0;
        $result['entries'][$date][$shift_key]['failed_sales'] = isset($result['entries'][$date][$shift_key]['failed_sales']) ? $result['entries'][$date][$shift_key]['failed_sales'] : 0;

        if ($staff_user_id) {
            $result['entries'][$date][$shift_key]['staff_user_id'] = $staff_user_id;
        }
    }

    $saved = twmp_revenue_shifts_save_entries($branch_id, $month, $result['entries'], ['cash_sales', 'bank_transfer_sales', 'failed_sales']);

    return rest_ensure_response([
        'entries' => $result['entries'],
        'summary' => $result['summary'],
        'saved'   => $saved,
        'message' => twmp_revenue_shifts_get_import_message($result['summary']),
    ]);
}

function twmp_revenue_shifts_get_import_message($summary)
{
    return sprintf(
        /* translators: 1: order count, 2: cash total, 3: bank total, 4: failed total, 5: skipped order count */
        __('Đã lấy %1$d đơn. Tiền mặt: %2$s. Chuyển khoản: %3$s. Đơn lỗi: %4$s. Bỏ qua: %5$d đơn.', 'twmp-revenue-shifts'),
        isset($summary['orders_count']) ? (int) $summary['orders_count'] : 0,
        twmp_revenue_shifts_format_money(isset($summary['cash_total']) ? $summary['cash_total'] : 0),
        twmp_revenue_shifts_format_money(isset($summary['bank_total']) ? $summary['bank_total'] : 0),
        twmp_revenue_shifts_format_money(isset($summary['failed_total']) ? $summary['failed_total'] : 0),
        isset($summary['skipped_orders_count']) ? (int) $summary['skipped_orders_count'] : 0
    );
}

function twmp_revenue_shifts_import_orders($branch_id, $month, $date = '', $exclude_order_ids = array(), $staff_user_id = 0)
{
    $branch_id = absint($branch_id);
    $staff_user_id = absint($staff_user_id);
    $range = $date ? twmp_revenue_shifts_get_day_range($date) : twmp_revenue_shifts_get_month_range($month);
    $orders = twmp_revenue_shifts_get_orders_for_import($branch_id, $range['start'], $range['end'], $exclude_order_ids, $staff_user_id);
    $cash_methods = apply_filters('twmp_revenue_shifts_cash_payment_methods', ['cod']);
    $bank_methods = apply_filters('twmp_revenue_shifts_bank_payment_methods', ['bacs']);
    $entries = [];
    $summary = [
        'orders_count' => 0,
        'cash_orders_count' => 0,
        'bank_orders_count' => 0,
        'failed_orders_count' => 0,
        'skipped_orders_count' => 0,
        'cash_total' => 0,
        'bank_total' => 0,
        'failed_total' => 0,
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
                'failed_sales' => 0,
            ];
        }

        $payment_method = (string) $order->get_payment_method();
        $total = (int) round((float) $order->get_total());
        $summary['orders_count']++;

        if ($order->has_status('failed')) {
            $entries[$business_date][$shift_key]['failed_sales'] += $total;
            $summary['failed_orders_count']++;
            $summary['failed_total'] += $total;
            continue;
        }

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

function twmp_revenue_shifts_get_orders_for_import($branch_id, $start, $end, $exclude_order_ids = array(), $staff_user_id = 0)
{
    $statuses = apply_filters('twmp_revenue_shifts_import_order_statuses', ['on-hold', 'processing', 'completed', 'failed']);
    $branch_meta_key = defined('TWMP_STAFF_ORDERS_ORDER_BRANCH_META') ? TWMP_STAFF_ORDERS_ORDER_BRANCH_META : '_twmp_branch_id';
    $exclude_order_ids = array_values(array_filter(array_map('absint', (array) $exclude_order_ids)));
    $staff_user_id = absint($staff_user_id);

    $args = [
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
    ];

    if (!empty($exclude_order_ids)) {
        $args['exclude'] = $exclude_order_ids;
    }

    if ($staff_user_id) {
        $args['customer_id'] = $staff_user_id;
    }

    return wc_get_orders($args);
}

function twmp_revenue_shifts_get_import_statuses()
{
    return array_values(array_unique(array_map('sanitize_key', (array) apply_filters('twmp_revenue_shifts_import_order_statuses', ['on-hold', 'processing', 'completed', 'failed']))));
}

function twmp_revenue_shifts_get_order_branch_id($order)
{
    if (!$order instanceof WC_Order) {
        return 0;
    }

    $branch_meta_key = defined('TWMP_STAFF_ORDERS_ORDER_BRANCH_META') ? TWMP_STAFF_ORDERS_ORDER_BRANCH_META : '_twmp_branch_id';

    return absint($order->get_meta($branch_meta_key, true));
}

function twmp_revenue_shifts_sync_order_revenue_from_hook($order_id, $order = null)
{
    if (!$order instanceof WC_Order) {
        $order = wc_get_order($order_id);
    }

    if (!$order instanceof WC_Order) {
        return;
    }

    twmp_revenue_shifts_sync_month_from_order($order);
}

function twmp_revenue_shifts_maybe_sync_order_revenue($order_id, $from, $to, $order = null)
{
    $from = sanitize_key((string) $from);
    $to   = sanitize_key((string) $to);
    $import_statuses = twmp_revenue_shifts_get_import_statuses();

    if (!in_array($from, $import_statuses, true) && !in_array($to, $import_statuses, true)) {
        return;
    }

    if (!$order instanceof WC_Order) {
        $order = wc_get_order($order_id);
    }

    if (!$order instanceof WC_Order) {
        return;
    }

    twmp_revenue_shifts_sync_month_from_order($order, false);
}

function twmp_revenue_shifts_sync_month_from_order($order, $exclude_current_order = true)
{
    static $synced_months = array();

    if (!$order instanceof WC_Order || !function_exists('wc_get_orders')) {
        return false;
    }

    $branch_id = twmp_revenue_shifts_get_order_branch_id($order);
    $date = $order->get_date_created();
    $exclude_order_ids = $exclude_current_order ? array($order->get_id()) : array();

    if (!$branch_id || !$date instanceof WC_DateTime) {
        return false;
    }

    $month = $date->date_i18n('Y-m');
    $sync_key = $branch_id . '|' . $month;

    if (isset($synced_months[$sync_key])) {
        return true;
    }

    $result = twmp_revenue_shifts_import_orders($branch_id, $month, '', $exclude_order_ids);
    $business_date = $date->date_i18n('Y-m-d');
    $shift_key = twmp_revenue_shifts_get_shift_key_for_datetime($date);

    if (empty($result['entries']) || !is_array($result['entries'])) {
        $result['entries'] = array();
    }

    if (!isset($result['entries'][$business_date][$shift_key])) {
        $result['entries'][$business_date][$shift_key] = array(
            'cash_sales' => 0,
            'bank_transfer_sales' => 0,
            'failed_sales' => 0,
        );
    }

    twmp_revenue_shifts_save_entries($branch_id, $month, $result['entries'], ['cash_sales', 'bank_transfer_sales', 'failed_sales']);
    $synced_months[$sync_key] = true;

    return true;
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

function twmp_revenue_shifts_get_day_range($date)
{
    $timezone = wp_timezone();
    $start = new DateTimeImmutable($date . ' 00:00:00', $timezone);
    $end = $start->setTime(23, 59, 59);

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

function twmp_revenue_shifts_save_entries($branch_id, $month, $entries, $allowed_money_fields = null)
{
    global $wpdb;

    $branch_id = absint($branch_id);
    $table = twmp_revenue_shifts_table();
    $saved = 0;
    $valid_shifts = ['morning', 'afternoon'];
    $money_fields = twmp_revenue_shifts_money_fields();
    $allowed_money_fields = is_array($allowed_money_fields) ? array_values(array_intersect($allowed_money_fields, $money_fields)) : $money_fields;
    $now = current_time('mysql');

    foreach ($entries as $date => $shift_entries) {
        if (!is_array($shift_entries) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) || 0 !== strpos((string) $date, $month . '-')) {
            continue;
        }

        foreach ($shift_entries as $shift_key => $entry) {
            if (!in_array($shift_key, $valid_shifts, true) || !is_array($entry)) {
                continue;
            }

            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE branch_id = %d AND business_date = %s AND shift_key = %s LIMIT 1",
                    $branch_id,
                    $date,
                    $shift_key
                ),
                ARRAY_A
            );

            $data = [
                'branch_id'            => $branch_id,
                'business_date'        => $date,
                'shift_key'            => $shift_key,
                'staff_user_id'        => isset($entry['staff_user_id']) ? absint($entry['staff_user_id']) : (isset($existing['staff_user_id']) ? absint($existing['staff_user_id']) : get_current_user_id()),
                'opening_cash'         => isset($existing['opening_cash']) ? (int) $existing['opening_cash'] : 0,
                'cash_sales'           => isset($existing['cash_sales']) ? (int) $existing['cash_sales'] : 0,
                'exchange_cash_out'    => isset($existing['exchange_cash_out']) ? (int) $existing['exchange_cash_out'] : 0,
                'expenses_cash'        => isset($existing['expenses_cash']) ? (int) $existing['expenses_cash'] : 0,
                'bank_transfer_sales'  => isset($existing['bank_transfer_sales']) ? (int) $existing['bank_transfer_sales'] : 0,
                'failed_sales'         => isset($existing['failed_sales']) ? (int) $existing['failed_sales'] : 0,
                'status'               => 'draft',
                'updated_at'           => $now,
            ];

            foreach ($money_fields as $field) {
                if (in_array($field, $allowed_money_fields, true) && isset($entry[$field])) {
                    $data[$field] = twmp_revenue_shifts_parse_money($entry[$field]);
                }
            }

            $existing_id = isset($existing['id']) ? absint($existing['id']) : 0;

            if ($existing_id) {
                $wpdb->update(
                    $table,
                    $data,
                    ['id' => absint($existing_id)],
                    ['%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s'],
                    ['%d']
                );
            } else {
                $data['created_at'] = $now;
                $wpdb->insert(
                    $table,
                    $data,
                    ['%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s']
                );
            }

            $saved++;
        }
    }

    return $saved;
}
