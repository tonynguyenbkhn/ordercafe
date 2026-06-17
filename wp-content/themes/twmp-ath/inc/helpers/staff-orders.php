<?php
/**
 * Staff order board helpers.
 *
 * @package twmp-ath
 */

if (!defined('ABSPATH')) {
    exit;
}

const TWMP_STAFF_ORDERS_BRANCH_POST_TYPE = 'twmp_branch';
const TWMP_STAFF_ORDERS_USER_BRANCH_META = 'twmp_staff_branch_id';
const TWMP_STAFF_ORDERS_ORDER_BRANCH_META = '_twmp_branch_id';

add_action('init', 'twmp_staff_orders_register_branch_post_type');
add_action('show_user_profile', 'twmp_staff_orders_render_user_branch_field');
add_action('edit_user_profile', 'twmp_staff_orders_render_user_branch_field');
add_action('personal_options_update', 'twmp_staff_orders_save_user_branch_field');
add_action('edit_user_profile_update', 'twmp_staff_orders_save_user_branch_field');
add_action('woocommerce_admin_order_data_after_order_details', 'twmp_staff_orders_render_admin_order_branch_field');
add_action('woocommerce_process_shop_order_meta', 'twmp_staff_orders_save_admin_order_branch_field', 10, 2);
add_filter('woocommerce_checkout_fields', 'twmp_staff_orders_add_checkout_branch_field', 30);
add_filter('woocommerce_checkout_get_value', 'twmp_staff_orders_force_checkout_branch_field_value', 10, 2);
add_action('woocommerce_checkout_process', 'twmp_staff_orders_validate_checkout_branch_field');
add_action('woocommerce_checkout_create_order', 'twmp_staff_orders_save_checkout_branch_field', 10, 2);
add_action('template_redirect', 'twmp_staff_orders_handle_status_update');

function twmp_staff_orders_register_branch_post_type()
{
    register_post_type(
        TWMP_STAFF_ORDERS_BRANCH_POST_TYPE,
        array(
            'labels' => array(
                'name'               => __('Branches', 'twmp-ath'),
                'singular_name'      => __('Branch', 'twmp-ath'),
                'add_new_item'       => __('Add New Branch', 'twmp-ath'),
                'edit_item'          => __('Edit Branch', 'twmp-ath'),
                'new_item'           => __('New Branch', 'twmp-ath'),
                'view_item'          => __('View Branch', 'twmp-ath'),
                'search_items'       => __('Search Branches', 'twmp-ath'),
                'not_found'          => __('No branches found.', 'twmp-ath'),
                'not_found_in_trash' => __('No branches found in trash.', 'twmp-ath'),
                'menu_name'          => __('Branches', 'twmp-ath'),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-store',
            'supports'     => array('title', 'editor'),
            'capability_type' => 'post',
        )
    );
}

function twmp_staff_orders_get_branch_options($include_empty = false)
{
    $options = $include_empty ? array('' => __('Select branch', 'twmp-ath')) : array();
    $branches = get_posts(
        array(
            'post_type'      => TWMP_STAFF_ORDERS_BRANCH_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );

    foreach ($branches as $branch) {
        $options[$branch->ID] = get_the_title($branch);
    }

    return $options;
}

function twmp_staff_orders_get_user_branch_id($user_id = 0)
{
    $user_id = $user_id ? absint($user_id) : get_current_user_id();

    return absint(get_user_meta($user_id, TWMP_STAFF_ORDERS_USER_BRANCH_META, true));
}

function twmp_staff_orders_get_branch_name($branch_id)
{
    $branch_id = absint($branch_id);

    return $branch_id ? get_the_title($branch_id) : '';
}

function twmp_staff_orders_is_valid_branch($branch_id)
{
    $branch_id = absint($branch_id);

    return $branch_id && TWMP_STAFF_ORDERS_BRANCH_POST_TYPE === get_post_type($branch_id) && 'publish' === get_post_status($branch_id);
}

function twmp_staff_orders_current_user_has_role($role)
{
    $user = wp_get_current_user();

    return $user instanceof WP_User && in_array($role, (array) $user->roles, true);
}

function twmp_staff_orders_current_user_can_view_board()
{
    if (!is_user_logged_in()) {
        return false;
    }

    if (twmp_staff_orders_current_user_has_role('administrator')) {
        return true;
    }

    return twmp_staff_orders_current_user_has_role('shop_manager') && twmp_staff_orders_get_user_branch_id();
}

function twmp_staff_orders_current_user_can_manage_all_orders()
{
    return twmp_staff_orders_current_user_has_role('administrator');
}

function twmp_staff_orders_current_user_can_assign_branches()
{
    return twmp_staff_orders_current_user_has_role('administrator');
}

function twmp_staff_orders_get_order_branch_id($order)
{
    if (!$order instanceof WC_Order) {
        return 0;
    }

    return absint($order->get_meta(TWMP_STAFF_ORDERS_ORDER_BRANCH_META, true));
}

function twmp_staff_orders_user_can_access_order($order, $user_id = 0)
{
    if (!$order instanceof WC_Order) {
        return false;
    }

    if (twmp_staff_orders_current_user_can_manage_all_orders()) {
        return true;
    }

    $user_branch_id = twmp_staff_orders_get_user_branch_id($user_id);

    return $user_branch_id && $user_branch_id === twmp_staff_orders_get_order_branch_id($order);
}

function twmp_staff_orders_get_allowed_statuses()
{
    if (!function_exists('wc_get_order_statuses')) {
        return array();
    }

    $statuses = wc_get_order_statuses();
    $allowed  = array();

    foreach (array('wc-on-hold', 'wc-processing', 'wc-completed') as $status_key) {
        if (isset($statuses[$status_key])) {
            $allowed[$status_key] = $statuses[$status_key];
        }
    }

    return apply_filters('twmp_staff_orders_allowed_statuses', $allowed);
}

function twmp_staff_orders_get_payment_methods()
{
    return apply_filters(
        'twmp_staff_orders_payment_methods',
        array(
            'cod'  => __('Tiền mặt', 'twmp-ath'),
            'bacs' => __('Chuyển khoản', 'twmp-ath'),
        )
    );
}

function twmp_staff_orders_get_payment_method_label($payment_method)
{
    $payment_methods = twmp_staff_orders_get_payment_methods();

    return isset($payment_methods[$payment_method]) ? $payment_methods[$payment_method] : $payment_method;
}

function twmp_staff_orders_update_order_payment_method($order, $payment_method)
{
    if (!$order instanceof WC_Order) {
        return false;
    }

    $payment_methods = twmp_staff_orders_get_payment_methods();

    if (!isset($payment_methods[$payment_method])) {
        return false;
    }

    $label = $payment_methods[$payment_method];

    if (function_exists('WC') && WC()->payment_gateways()) {
        $gateways = WC()->payment_gateways()->payment_gateways();

        if (isset($gateways[$payment_method])) {
            $order->set_payment_method($gateways[$payment_method]);
        } else {
            $order->set_payment_method($payment_method);
        }
    } else {
        $order->set_payment_method($payment_method);
    }

    $order->set_payment_method_title($label);
    $order->add_order_note(
        sprintf(
            /* translators: 1: payment method label, 2: staff display name */
            __('Payment method changed to %1$s from staff order board by %2$s.', 'twmp-ath'),
            $label,
            wp_get_current_user()->display_name
        )
    );
    $order->save();

    return true;
}

function twmp_staff_orders_render_user_branch_field($user)
{
    if (!twmp_staff_orders_current_user_can_assign_branches()) {
        return;
    }

    $branch_id = twmp_staff_orders_get_user_branch_id($user->ID);
    $branches  = twmp_staff_orders_get_branch_options(true);
    ?>
    <h2><?php esc_html_e('Staff Branch', 'twmp-ath'); ?></h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="twmp_staff_branch_id"><?php esc_html_e('Branch', 'twmp-ath'); ?></label></th>
            <td>
                <select name="twmp_staff_branch_id" id="twmp_staff_branch_id">
                    <?php foreach ($branches as $id => $label) : ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected((string) $branch_id, (string) $id); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Orders on the staff board are filtered by this branch.', 'twmp-ath'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

function twmp_staff_orders_save_user_branch_field($user_id)
{
    if (!twmp_staff_orders_current_user_can_assign_branches() || !current_user_can('edit_user', $user_id)) {
        return;
    }

    $branch_id = isset($_POST['twmp_staff_branch_id']) ? absint(wp_unslash($_POST['twmp_staff_branch_id'])) : 0;

    if ($branch_id && !twmp_staff_orders_is_valid_branch($branch_id)) {
        return;
    }

    if ($branch_id) {
        update_user_meta($user_id, TWMP_STAFF_ORDERS_USER_BRANCH_META, $branch_id);
    } else {
        delete_user_meta($user_id, TWMP_STAFF_ORDERS_USER_BRANCH_META);
    }
}

function twmp_staff_orders_render_admin_order_branch_field($order)
{
    if (!$order instanceof WC_Order || !current_user_can('edit_shop_order', $order->get_id())) {
        return;
    }

    $branches = twmp_staff_orders_get_branch_options(true);

    woocommerce_wp_select(
        array(
            'id'            => 'twmp_order_branch_id',
            'label'         => __('Branch', 'twmp-ath'),
            'wrapper_class' => 'form-field-wide',
            'value'         => twmp_staff_orders_get_order_branch_id($order),
            'options'       => $branches,
        )
    );
}

function twmp_staff_orders_save_admin_order_branch_field($order_id, $post)
{
    if (!current_user_can('edit_shop_order', $order_id) || !isset($_POST['twmp_order_branch_id'])) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $branch_id = absint(wp_unslash($_POST['twmp_order_branch_id']));
    if ($branch_id && !twmp_staff_orders_is_valid_branch($branch_id)) {
        return;
    }

    if ($branch_id) {
        $order->update_meta_data(TWMP_STAFF_ORDERS_ORDER_BRANCH_META, $branch_id);
    } else {
        $order->delete_meta_data(TWMP_STAFF_ORDERS_ORDER_BRANCH_META);
    }

    $order->save();
}

function twmp_staff_orders_add_checkout_branch_field($fields)
{
    if (!is_checkout()) {
        return $fields;
    }

    $branch_id = twmp_staff_orders_get_user_branch_id();

    if (!$branch_id || !twmp_staff_orders_is_valid_branch($branch_id)) {
        return $fields;
    }

    $fields['billing']['twmp_branch_id'] = array(
        'type'              => 'text',
        'label'             => __('Chi nhánh', 'twmp-ath'),
        'required'          => false,
        'class'             => array('form-row-wide'),
        'priority'          => 30,
        'default'           => twmp_staff_orders_get_branch_name($branch_id),
        'custom_attributes' => array(
            'readonly' => 'readonly',
        ),
    );

    return $fields;
}

function twmp_staff_orders_force_checkout_branch_field_value($value, $input)
{
    if ('twmp_branch_id' !== $input || !is_checkout()) {
        return $value;
    }

    return twmp_staff_orders_get_branch_name(twmp_staff_orders_get_user_branch_id());
}

function twmp_staff_orders_validate_checkout_branch_field()
{
    $branch_id = twmp_staff_orders_get_user_branch_id();

    if (!$branch_id || !twmp_staff_orders_is_valid_branch($branch_id)) {
        wc_add_notice(__('Tài khoản của bạn chưa được gán chi nhánh.', 'twmp-ath'), 'error');
    }
}

function twmp_staff_orders_save_checkout_branch_field($order, $data)
{
    if (!$order instanceof WC_Order) {
        return;
    }

    $branch_id = twmp_staff_orders_get_user_branch_id();

    if ($branch_id && twmp_staff_orders_is_valid_branch($branch_id)) {
        $order->update_meta_data(TWMP_STAFF_ORDERS_ORDER_BRANCH_META, $branch_id);
    }
}

function twmp_staff_orders_handle_status_update()
{
    if (empty($_POST['twmp_staff_order_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['twmp_staff_order_action']));

    if (!in_array($action, array('update_status', 'update_payment_method'), true)) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_die(esc_html__('You must log in to update orders.', 'twmp-ath'));
    }

    if (!function_exists('wc_get_order')) {
        wp_die(esc_html__('WooCommerce is required to update orders.', 'twmp-ath'));
    }

    check_admin_referer('twmp_staff_order_update_status', 'twmp_staff_order_nonce');

    $order_id   = isset($_POST['twmp_order_id']) ? absint(wp_unslash($_POST['twmp_order_id'])) : 0;
    $new_status = isset($_POST['twmp_order_status']) ? sanitize_key(wp_unslash($_POST['twmp_order_status'])) : '';
    $order      = wc_get_order($order_id);

    if (!$order || !twmp_staff_orders_user_can_access_order($order)) {
        wp_die(esc_html__('You cannot update this order.', 'twmp-ath'));
    }

    $redirect_url = !empty($_POST['twmp_staff_redirect'])
        ? esc_url_raw(wp_unslash($_POST['twmp_staff_redirect']))
        : wp_get_referer();

    if (!$redirect_url) {
        $redirect_url = home_url('/staff-orders/');
    }

    if ('update_payment_method' === $action) {
        $payment_method = isset($_POST['twmp_payment_method']) ? sanitize_key(wp_unslash($_POST['twmp_payment_method'])) : '';

        if (!twmp_staff_orders_update_order_payment_method($order, $payment_method)) {
            wp_die(esc_html__('Invalid payment method.', 'twmp-ath'));
        }

        wp_safe_redirect(add_query_arg('twmp_staff_payment_updated', '1', $redirect_url));
        exit;
    }

    if ('completed' === $order->get_status()) {
        wp_die(esc_html__('Completed orders cannot be changed here.', 'twmp-ath'));
    }

    $allowed_statuses = twmp_staff_orders_get_allowed_statuses();
    $status_key       = 'wc-' . $new_status;

    if (!$new_status || !isset($allowed_statuses[$status_key])) {
        wp_die(esc_html__('Invalid order status.', 'twmp-ath'));
    }

    $order->update_status(
        $new_status,
        sprintf(
            /* translators: %s: staff display name */
            __('Updated from staff order board by %s.', 'twmp-ath'),
            wp_get_current_user()->display_name
        ),
        true
    );

    wp_safe_redirect(add_query_arg('twmp_staff_updated', '1', $redirect_url));
    exit;
}

function twmp_staff_orders_get_query_branch_id()
{
    if (!twmp_staff_orders_current_user_can_manage_all_orders()) {
        return twmp_staff_orders_get_user_branch_id();
    }

    return isset($_GET['branch_id']) ? absint(wp_unslash($_GET['branch_id'])) : 0;
}

function twmp_staff_orders_get_query_status()
{
    $status = isset($_GET['order_status']) ? sanitize_key(wp_unslash($_GET['order_status'])) : 'all';

    return $status ? $status : 'all';
}

function twmp_staff_orders_get_query_order_id()
{
    return isset($_GET['twmp_order_id']) ? absint(wp_unslash($_GET['twmp_order_id'])) : 0;
}

function twmp_staff_orders_get_query_order_date()
{
    $date = isset($_GET['order_date']) ? sanitize_text_field(wp_unslash($_GET['order_date'])) : current_time('Y-m-d');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return current_time('Y-m-d');
    }

    return $date;
}

function twmp_staff_orders_get_day_range($date)
{
    $timezone = wp_timezone();
    $start = new DateTimeImmutable($date . ' 00:00:00', $timezone);
    $end = $start->setTime(23, 59, 59);

    return array(
        'start' => $start,
        'end'   => $end,
    );
}

function twmp_staff_orders_get_board_statuses()
{
    if (!function_exists('wc_get_order_statuses')) {
        return array('on-hold', 'processing', 'completed');
    }

    $statuses = array();
    $board_statuses = apply_filters('twmp_staff_orders_board_statuses', array('on-hold', 'processing', 'completed'));

    foreach (array_keys(wc_get_order_statuses()) as $status_key) {
        $status = str_replace('wc-', '', $status_key);

        if (in_array($status, $board_statuses, true)) {
            $statuses[] = $status;
        }
    }

    return $statuses;
}

function twmp_staff_orders_get_orders()
{
    if (!function_exists('wc_get_orders')) {
        return array();
    }

    if (!twmp_staff_orders_current_user_can_view_board()) {
        return array();
    }

    $branch_id = twmp_staff_orders_get_query_branch_id();
    $status    = twmp_staff_orders_get_query_status();
    $order_id  = twmp_staff_orders_get_query_order_id();
    $date      = twmp_staff_orders_get_query_order_date();
    $range     = twmp_staff_orders_get_day_range($date);
    $args      = array(
        'limit'   => 50,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'objects',
        'status'  => twmp_staff_orders_get_board_statuses(),
        'date_created' => $range['start']->getTimestamp() . '...' . $range['end']->getTimestamp(),
    );

    if ($order_id) {
        $args['post__in'] = array($order_id);
        $args['limit']    = 1;
    }

    if ($branch_id) {
        $args['meta_key']   = TWMP_STAFF_ORDERS_ORDER_BRANCH_META;
        $args['meta_value'] = $branch_id;
    }

    if (!in_array($status, array('open', 'all'), true)) {
        $allowed_statuses = wc_get_order_statuses();
        $status_key       = 'wc-' . $status;

        if (in_array($status, twmp_staff_orders_get_board_statuses(), true) && isset($allowed_statuses[$status_key])) {
            $args['status'] = array($status);
        }
    }

    return wc_get_orders($args);
}
