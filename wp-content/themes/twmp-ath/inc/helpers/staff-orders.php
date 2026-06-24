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
add_action('rest_api_init', 'twmp_staff_orders_register_rest_routes');

function twmp_staff_orders_register_rest_routes()
{
    register_rest_route('twmp-ath/v1', '/staff-orders', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'twmp_staff_orders_rest_poll',
        'permission_callback' => 'twmp_staff_orders_current_user_can_view_board',
    ));

    register_rest_route('twmp-ath/v1', '/staff-orders/update', array(
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => 'twmp_staff_orders_rest_update',
        'permission_callback' => 'twmp_staff_orders_current_user_can_view_board',
    ));

    register_rest_route('twmp-ath/v1', '/staff-orders/create', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'twmp_staff_orders_rest_create',
        'permission_callback' => 'twmp_staff_orders_current_user_can_view_board',
    ));
}

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

    foreach (array('wc-processing', 'wc-completed') as $status_key) {
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

    $redirect_url = !empty($_POST['twmp_staff_redirect'])
        ? esc_url_raw(wp_unslash($_POST['twmp_staff_redirect']))
        : wp_get_referer();

    if (!$redirect_url) {
        $redirect_url = home_url('/staff-orders/');
    }

    $result = twmp_staff_orders_update_order_from_request($action);

    if (is_wp_error($result)) {
        wp_die(esc_html($result->get_error_message()));
    }

    if ('update_payment_method' === $action) {
        wp_safe_redirect(add_query_arg('twmp_staff_payment_updated', '1', $redirect_url));
        exit;
    }

    wp_safe_redirect(add_query_arg('twmp_staff_updated', '1', $redirect_url));
    exit;
}

function twmp_staff_orders_update_order_from_request($action)
{
    $order_id   = isset($_POST['twmp_order_id']) ? absint(wp_unslash($_POST['twmp_order_id'])) : 0;
    $new_status = isset($_POST['twmp_order_status']) ? sanitize_key(wp_unslash($_POST['twmp_order_status'])) : '';
    $order      = wc_get_order($order_id);

    if (!$order || !twmp_staff_orders_user_can_access_order($order)) {
        return new WP_Error('twmp_staff_order_forbidden', __('You cannot update this order.', 'twmp-ath'));
    }

    if ('update_payment_method' === $action) {
        $payment_method = isset($_POST['twmp_payment_method']) ? sanitize_key(wp_unslash($_POST['twmp_payment_method'])) : '';

        if (!twmp_staff_orders_update_order_payment_method($order, $payment_method)) {
            return new WP_Error('twmp_staff_order_invalid_payment', __('Invalid payment method.', 'twmp-ath'));
        }

        return twmp_staff_orders_get_order_response_data($order);
    }

    //    if ('completed' === $order->get_status()) {
    //        return new WP_Error('twmp_staff_order_completed', __('Completed orders cannot be changed here.', 'twmp-ath'));
    //    }

    $allowed_statuses = twmp_staff_orders_get_allowed_statuses();
    $status_key       = 'wc-' . $new_status;

    if (!$new_status || !isset($allowed_statuses[$status_key])) {
        return new WP_Error('twmp_staff_order_invalid_status', __('Invalid order status.', 'twmp-ath'));
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

    return twmp_staff_orders_get_order_response_data($order);
}

function twmp_staff_orders_get_order_response_data($order)
{
    if (!$order instanceof WC_Order) {
        return array();
    }

    $payment_method = $order->get_payment_method();

    return array(
        'order_id'             => $order->get_id(),
        'status'               => $order->get_status(),
        'status_name'          => wc_get_order_status_name($order->get_status()),
        'payment_method'       => $payment_method,
        'payment_method_label' => twmp_staff_orders_get_payment_method_label($payment_method),
        'signature'            => twmp_staff_orders_get_orders_signature(function_exists('twmp_staff_orders_get_orders_with_fallback') ? twmp_staff_orders_get_orders_with_fallback() : twmp_staff_orders_get_orders()),
    );
}

function twmp_staff_orders_get_default_create_branch_id(WP_REST_Request $request)
{
    $branch_id = absint($request->get_param('branch_id'));

    if ($branch_id && twmp_staff_orders_is_valid_branch($branch_id) && twmp_staff_orders_current_user_can_manage_all_orders()) {
        return $branch_id;
    }

    $branch_id = twmp_staff_orders_get_user_branch_id();

    if ($branch_id && twmp_staff_orders_is_valid_branch($branch_id)) {
        return $branch_id;
    }

    if (twmp_staff_orders_current_user_can_manage_all_orders()) {
        $branches = twmp_staff_orders_get_branch_options(false);
        $first_branch_id = absint(key($branches));

        if ($first_branch_id && twmp_staff_orders_is_valid_branch($first_branch_id)) {
            return $first_branch_id;
        }
    }

    return 0;
}

function twmp_staff_orders_add_cart_item_meta_to_order_item($item, $cart_item)
{
    if (!$item instanceof WC_Order_Item_Product || !is_array($cart_item)) {
        return;
    }

    if (!empty($cart_item['twmp_note'])) {
        $item->add_meta_data(__('Ghi chÃº', 'twmp-ath'), wc_clean($cart_item['twmp_note']), true);
    }

    if (!empty($cart_item['twmp_staff_notes']) && is_array($cart_item['twmp_staff_notes'])) {
        foreach ($cart_item['twmp_staff_notes'] as $key => $value) {
            $label = wc_attribute_label($key);
            if ($label === $key) {
                $label = ucwords(str_replace(array('pa_', '-', '_'), array('', ' ', ' '), (string) $key));
            }

            $item->add_meta_data($label, wc_clean($value), false);
        }
    }
}

function twmp_staff_orders_set_order_payment_method($order, $payment_method)
{
    if (!$order instanceof WC_Order) {
        return;
    }

    $payment_methods = twmp_staff_orders_get_payment_methods();
    $label = isset($payment_methods[$payment_method]) ? $payment_methods[$payment_method] : $payment_method;

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
}

function twmp_staff_orders_rest_create(WP_REST_Request $request)
{
    if (!function_exists('wc_create_order') || !function_exists('WC')) {
        return new WP_Error('twmp_staff_order_woocommerce_missing', __('WooCommerce is required to create orders.', 'twmp-ath'), array('status' => 500));
    }

    if (function_exists('twmp_cafe_menu_ensure_cart')) {
        twmp_cafe_menu_ensure_cart();
    } elseif (null === WC()->cart && function_exists('wc_load_cart')) {
        wc_load_cart();
    }

    if (null === WC()->cart || WC()->cart->is_empty()) {
        return new WP_Error('twmp_staff_order_empty_cart', __('Cart is empty.', 'twmp-ath'), array('status' => 400));
    }

    $branch_id = twmp_staff_orders_get_default_create_branch_id($request);

    if (!$branch_id) {
        return new WP_Error('twmp_staff_order_missing_branch', __('Staff account is not assigned to a valid branch.', 'twmp-ath'), array('status' => 403));
    }

    $payment_methods = twmp_staff_orders_get_payment_methods();
    $payment_method = sanitize_key((string) $request->get_param('payment_method'));

    if (!$payment_method) {
        $payment_method = 'cod';
    }

    if (!isset($payment_methods[$payment_method])) {
        return new WP_Error('twmp_staff_order_invalid_payment', __('Invalid payment method.', 'twmp-ath'), array('status' => 400));
    }

    $current_user = wp_get_current_user();
    $customer_name = sanitize_text_field((string) $request->get_param('customer_name'));
    $customer_phone = sanitize_text_field((string) $request->get_param('customer_phone'));

    if ('' === $customer_name && $current_user instanceof WP_User) {
        $customer_name = $current_user->display_name;
    }

    $order = wc_create_order(array(
        'customer_id' => get_current_user_id(),
        'created_via' => 'twmp_staff_order',
    ));

    if (is_wp_error($order)) {
        $order->add_data(array('status' => 500));
        return $order;
    }

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = isset($cart_item['data']) ? $cart_item['data'] : false;

        if (!$product instanceof WC_Product || !$product->exists()) {
            continue;
        }

        $item_id = $order->add_product(
            $product,
            isset($cart_item['quantity']) ? max(1, absint($cart_item['quantity'])) : 1,
            array(
                'variation' => isset($cart_item['variation']) && is_array($cart_item['variation']) ? $cart_item['variation'] : array(),
                'totals' => array(
                    'subtotal'     => isset($cart_item['line_subtotal']) ? $cart_item['line_subtotal'] : 0,
                    'subtotal_tax' => isset($cart_item['line_subtotal_tax']) ? $cart_item['line_subtotal_tax'] : 0,
                    'total'        => isset($cart_item['line_total']) ? $cart_item['line_total'] : 0,
                    'tax'          => isset($cart_item['line_tax']) ? $cart_item['line_tax'] : 0,
                    'tax_data'     => isset($cart_item['line_tax_data']) ? $cart_item['line_tax_data'] : array(),
                ),
            )
        );

        $item = $item_id ? $order->get_item($item_id) : false;
        if ($item instanceof WC_Order_Item_Product) {
            twmp_staff_orders_add_cart_item_meta_to_order_item($item, $cart_item);
            $item->save();
        }
    }

    if (!$order->get_items()) {
        $order->delete(true);
        return new WP_Error('twmp_staff_order_no_items', __('No valid cart items found.', 'twmp-ath'), array('status' => 400));
    }

    $order->set_billing_first_name($customer_name ? $customer_name : __('Guest', 'twmp-ath'));
    $order->set_billing_phone($customer_phone);
    twmp_staff_orders_set_order_payment_method($order, $payment_method);
    $order->update_meta_data(TWMP_STAFF_ORDERS_ORDER_BRANCH_META, $branch_id);
    $order->calculate_totals();
    $order->update_status(
        'processing',
        sprintf(
            /* translators: %s: staff display name */
            __('Created from staff cafe menu by %s.', 'twmp-ath'),
            $current_user instanceof WP_User ? $current_user->display_name : ''
        ),
        true
    );
    $order->save();

    WC()->cart->empty_cart(true);
    if (function_exists('twmp_cafe_menu_persist_cart')) {
        twmp_cafe_menu_persist_cart();
    }

    $orders = twmp_staff_orders_get_orders_with_fallback();

    return rest_ensure_response(array(
        'success'          => true,
        'order_id'         => $order->get_id(),
        'order_number'     => $order->get_order_number(),
        'status'           => $order->get_status(),
        'status_name'      => wc_get_order_status_name($order->get_status()),
        'staff_orders_url' => add_query_arg(
            array(
                'twmp_order_id'      => $order->get_id(),
                'order_status'       => 'all',
                'twmp_order_created' => '1',
            ),
            '/staff-orders/'
        ),
        'signature'        => twmp_staff_orders_get_orders_signature($orders),
        'cart'             => function_exists('twmp_cafe_menu_render_cart_shell') ? twmp_cafe_menu_render_cart_shell() : null,
    ));
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
        return array('processing', 'completed');
    }

    $statuses = array();
    $board_statuses = apply_filters('twmp_staff_orders_board_statuses', array('processing', 'completed'));

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

// Fallback: try broader statuses if no orders found (handles gateways that set different initial statuses)
function twmp_staff_orders_get_orders_with_fallback()
{
    $orders = twmp_staff_orders_get_orders();

    if (!empty($orders)) {
        return $orders;
    }

    if (!function_exists('wc_get_orders')) {
        return array();
    }

    $branch_id = twmp_staff_orders_get_query_branch_id();
    $date      = twmp_staff_orders_get_query_order_date();
    $range     = twmp_staff_orders_get_day_range($date);

    $args = array(
        'limit'        => 50,
        'orderby'      => 'date',
        'order'        => 'DESC',
        'return'       => 'objects',
        'status'       => array('processing', 'completed', 'on-hold', 'pending'),
        'date_created' => $range['start']->getTimestamp() . '...' . $range['end']->getTimestamp(),
    );

    if ($branch_id) {
        $args['meta_key']   = TWMP_STAFF_ORDERS_ORDER_BRANCH_META;
        $args['meta_value'] = $branch_id;
    }

    return wc_get_orders($args);
}

function twmp_staff_orders_get_orders_signature($orders)
{
    $parts = array();

    foreach ((array) $orders as $order) {
        if (!$order instanceof WC_Order) {
            continue;
        }

        $modified = $order->get_date_modified();
        $parts[] = implode(':', array(
            $order->get_id(),
            $order->get_status(),
            $order->get_payment_method(),
            $modified instanceof WC_DateTime ? $modified->getTimestamp() : 0,
        ));
    }

    return md5(implode('|', $parts));
}

function twmp_staff_orders_render_table_rows($orders)
{
    $allowed_statuses = twmp_staff_orders_get_allowed_statuses();
    $payment_methods  = twmp_staff_orders_get_payment_methods();

    ob_start();

    if (empty($orders)) :
    ?>
        <tr>
            <td colspan="7" class="no-order"><?php esc_html_e('No orders found.', 'twmp-ath'); ?></td>
        </tr>
    <?php
    endif;

    foreach ((array) $orders as $order) :
        if (!$order instanceof WC_Order || !twmp_staff_orders_user_can_access_order($order)) {
            continue;
        }

        $order_date           = $order->get_date_created();
        $status_key           = 'wc-' . $order->get_status();
        $payment_method       = $order->get_payment_method();
        $payment_method_label = twmp_staff_orders_get_payment_method_label($payment_method);
    ?>
        <tr data-staff-order-row class="<?php echo esc_attr($status_key) ?>" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
            <td>
                <?php if (current_user_can('edit_shop_order', $order->get_id())) : ?>
                    <a class="twmp-staff-orders__order" href="<?php echo esc_url($order->get_edit_order_url()); ?>">
                        #<?php echo esc_html($order->get_order_number()); ?>
                    </a>
                <?php else : ?>
                    <strong class="twmp-staff-orders__order">#<?php echo esc_html($order->get_order_number()); ?></strong>
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
                            if ($item_meta) :
                            ?>
                                <div class="twmp-staff-orders__item-meta">
                                    <?php echo wp_kses_post($item_meta); ?>
                                </div>
                            <?php
                            endif;
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
            <td style="display: none;"><span class="twmp-staff-orders__status" data-staff-order-status-label><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span></td>
            <td>
                <form class="twmp-staff-orders__status-form" method="post" data-staff-order-status-form>
                    <?php wp_nonce_field('twmp_staff_order_update_status', 'twmp_staff_order_nonce'); ?>
                    <input type="hidden" name="twmp_staff_order_action" value="update_status">
                    <input type="hidden" name="twmp_order_id" value="<?php echo esc_attr($order->get_id()); ?>">
                    <input type="hidden" name="twmp_staff_redirect" value="<?php echo esc_url(get_permalink()); ?>">
                    <select name="twmp_order_status" aria-label="<?php esc_attr_e('New order status', 'twmp-ath'); ?>">
                        <?php foreach ($allowed_statuses as $allowed_key => $allowed_label) : ?>
                            <?php $allowed_value = str_replace('wc-', '', $allowed_key); ?>
                            <option value="<?php echo esc_attr($allowed_value); ?>" <?php selected($status_key, $allowed_key); ?>>
                                <?php echo esc_html($allowed_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <span class="twmp-staff-orders__meta" data-staff-order-payment-label>
                    <?php
                    printf(
                        /* translators: %s: payment method label */
                        esc_html__('Payment: %s', 'twmp-ath'),
                        esc_html($payment_method_label ? $payment_method_label : __('Unknown', 'twmp-ath'))
                    );
                    ?>
                </span>
                <form class="twmp-staff-orders__payment-form" method="post" data-staff-order-payment-form>
                    <?php wp_nonce_field('twmp_staff_order_update_status', 'twmp_staff_order_nonce'); ?>
                    <input type="hidden" name="twmp_staff_order_action" value="update_payment_method">
                    <input type="hidden" name="twmp_order_id" value="<?php echo esc_attr($order->get_id()); ?>">
                    <input type="hidden" name="twmp_staff_redirect" value="<?php echo esc_url(get_permalink()); ?>">
                    <?php foreach ($payment_methods as $method_key => $method_label) : ?>
                        <button
                            class="twmp-staff-orders__payment-button <?php echo $payment_method === $method_key ? 'is-active' : ''; ?>"
                            type="submit"
                            name="twmp_payment_method"
                            value="<?php echo esc_attr($method_key); ?>"
                            data-payment-label="<?php echo esc_attr($method_label); ?>"
                            <?php disabled($payment_method, $method_key); ?>>
                            <?php echo esc_html($method_label); ?>
                        </button>
                    <?php endforeach; ?>
                </form>
            </td>
        </tr>
<?php
    endforeach;

    return trim(ob_get_clean());
}


function twmp_staff_orders_apply_post_filters_to_query()
{
    foreach (array('branch_id', 'order_status', 'twmp_order_id', 'order_date') as $key) {
        if (isset($_POST[$key])) {
            $_GET[$key] = wp_unslash($_POST[$key]);
        }
    }
}

function twmp_staff_orders_apply_rest_request(WP_REST_Request $request)
{
    foreach ($request->get_params() as $key => $value) {
        if (is_array($value)) {
            continue;
        }

        $_POST[$key] = $value;
        $_GET[$key]  = $value;
    }
}

function twmp_staff_orders_rest_poll(WP_REST_Request $request)
{
    twmp_staff_orders_apply_rest_request($request);

    $orders = function_exists('twmp_staff_orders_get_orders_with_fallback') ? twmp_staff_orders_get_orders_with_fallback() : twmp_staff_orders_get_orders();
    $signature = twmp_staff_orders_get_orders_signature($orders);
    $client_signature = sanitize_text_field((string) $request->get_param('signature'));
    $response = array(
        'signature' => $signature,
        'count'     => count($orders),
    );

    if (!$client_signature || $client_signature !== $signature || $request->get_param('force_html')) {
        $response['html'] = function_exists('twmp_staff_orders_render_table_rows') ? twmp_staff_orders_render_table_rows($orders) : '';
    }

    return rest_ensure_response($response);
}

function twmp_staff_orders_rest_update(WP_REST_Request $request)
{
    twmp_staff_orders_apply_rest_request($request);

    $action = isset($_POST['twmp_staff_order_action']) ? sanitize_key(wp_unslash($_POST['twmp_staff_order_action'])) : '';

    if (!in_array($action, array('update_status', 'update_payment_method'), true)) {
        return new WP_Error('twmp_staff_order_invalid_action', __('Invalid order action.', 'twmp-ath'), array('status' => 400));
    }

    $result = twmp_staff_orders_update_order_from_request($action);

    if (is_wp_error($result)) {
        $result->add_data(array('status' => 400));
        return $result;
    }

    return rest_ensure_response($result);
}
