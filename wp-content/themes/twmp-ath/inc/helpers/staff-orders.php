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

    foreach (array('wc-pending', 'wc-processing', 'wc-completed') as $status_key) {
        if (isset($statuses[$status_key])) {
            $allowed[$status_key] = $statuses[$status_key];
        }
    }

    return apply_filters('twmp_staff_orders_allowed_statuses', $allowed);
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
    $branches = twmp_staff_orders_get_branch_options(true);

    if (count($branches) <= 1) {
        return $fields;
    }

    $fields['billing']['twmp_branch_id'] = array(
        'type'     => 'select',
        'label'    => __('Branch', 'twmp-ath'),
        'required' => true,
        'class'    => array('form-row-wide'),
        'priority' => 30,
        'default'  => twmp_staff_orders_get_user_branch_id(),
        'options'  => $branches,
    );

    return $fields;
}

function twmp_staff_orders_validate_checkout_branch_field()
{
    if (empty(twmp_staff_orders_get_branch_options())) {
        return;
    }

    $branch_id = isset($_POST['twmp_branch_id']) ? absint(wp_unslash($_POST['twmp_branch_id'])) : 0;

    if (!$branch_id || !twmp_staff_orders_is_valid_branch($branch_id)) {
        wc_add_notice(__('Please select a branch.', 'twmp-ath'), 'error');
    }
}

function twmp_staff_orders_save_checkout_branch_field($order, $data)
{
    if (!$order instanceof WC_Order) {
        return;
    }

    $branch_id = isset($_POST['twmp_branch_id']) ? absint(wp_unslash($_POST['twmp_branch_id'])) : 0;

    if (!$branch_id && is_user_logged_in()) {
        $branch_id = twmp_staff_orders_get_user_branch_id();
    }

    if ($branch_id && twmp_staff_orders_is_valid_branch($branch_id)) {
        $order->update_meta_data(TWMP_STAFF_ORDERS_ORDER_BRANCH_META, $branch_id);
    }
}

function twmp_staff_orders_handle_status_update()
{
    if (
        empty($_POST['twmp_staff_order_action']) ||
        'update_status' !== $_POST['twmp_staff_order_action']
    ) {
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

    $redirect_url = !empty($_POST['twmp_staff_redirect'])
        ? esc_url_raw(wp_unslash($_POST['twmp_staff_redirect']))
        : wp_get_referer();

    if (!$redirect_url) {
        $redirect_url = home_url('/staff-orders/');
    }

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
    $status = isset($_GET['order_status']) ? sanitize_key(wp_unslash($_GET['order_status'])) : 'open';

    return $status ? $status : 'open';
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
    $args      = array(
        'limit'   => 50,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'objects',
    );

    if ($branch_id) {
        $args['meta_key']   = TWMP_STAFF_ORDERS_ORDER_BRANCH_META;
        $args['meta_value'] = $branch_id;
    }

    if ('open' === $status) {
        $args['status'] = array('pending', 'processing');
    } elseif ('all' !== $status) {
        $allowed_statuses = wc_get_order_statuses();
        $status_key       = 'wc-' . $status;

        if (isset($allowed_statuses[$status_key])) {
            $args['status'] = array($status);
        }
    }

    return wc_get_orders($args);
}
