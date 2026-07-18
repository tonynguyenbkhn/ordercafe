<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TWMP_Combo_Cards {
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
        add_action( 'plugins_loaded', array( __CLASS__, 'maybe_update_db' ) );
    }

    public static function activate() {
        self::create_tables();
        update_option( 'twmp_combo_cards_db_version', TWMP_COMBO_CARDS_DB_VERSION );
    }

    public static function deactivate() {
    }

    public static function maybe_update_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twmp_combo_cards';

        if (
            get_option( 'twmp_combo_cards_db_version' ) !== TWMP_COMBO_CARDS_DB_VERSION
            || ! self::table_exists( $table_name )
            || ! self::table_has_column( $table_name, 'item_name' )
        ) {
            self::create_tables();
            update_option( 'twmp_combo_cards_db_version', TWMP_COMBO_CARDS_DB_VERSION );
        }
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'twmp_combo_cards';
        $log_table = $wpdb->prefix . 'twmp_combo_card_logs';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_name varchar(191) DEFAULT NULL,
            item_name varchar(191) DEFAULT NULL,
            phone varchar(50) NOT NULL,
            normalized_phone varchar(50) NOT NULL,
            combo_size tinyint(3) unsigned NOT NULL,
            purchased_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            status varchar(32) NOT NULL DEFAULT 'active',
            used_slots longtext NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY normalized_phone (normalized_phone),
            KEY status (status)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE IF NOT EXISTS {$log_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            card_id bigint(20) unsigned NOT NULL,
            slot tinyint(3) unsigned NOT NULL,
            action varchar(16) NOT NULL,
            performed_by bigint(20) unsigned DEFAULT NULL,
            performed_at datetime NOT NULL,
            note text,
            PRIMARY KEY  (id),
            KEY card_id (card_id),
            KEY performed_by (performed_by)
        ) $charset_collate;";

        dbDelta( $sql );
        dbDelta( $sql2 );

        if ( self::table_exists( $table_name ) && ! self::table_has_column( $table_name, 'item_name' ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD item_name varchar(191) DEFAULT NULL AFTER customer_name" );
        }
    }

    public static function register_shortcodes() {
        add_shortcode( 'twmp_combo_lookup', array( __CLASS__, 'render_lookup_shortcode' ) );
        add_shortcode( 'twmp_combo_manage', array( __CLASS__, 'render_manage_shortcode' ) );
    }

    public static function enqueue_assets() {
        wp_register_style( 'twmp-combo-cards-style', TWMP_COMBO_CARDS_PLUGIN_URL . 'assets/css/style.css', array(), TWMP_COMBO_CARDS_VERSION );
        wp_register_script( 'twmp-combo-cards-script', TWMP_COMBO_CARDS_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), TWMP_COMBO_CARDS_VERSION, true );
        wp_localize_script( 'twmp-combo-cards-script', 'TWMPComboCards', array(
            'restUrl' => esc_url_raw( rest_url( 'twmp-combo-cards/v1' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ) );
        wp_enqueue_style( 'twmp-combo-cards-style' );
        wp_enqueue_script( 'twmp-combo-cards-script' );
    }

    public static function render_lookup_shortcode( $atts ) {
        self::enqueue_assets();
        ob_start();
        ?>
        <div class="twmp-combo-lookup">
            <form id="twmp-combo-lookup-form" class="twmp-combo-form" method="post">
                <label for="twmp-combo-phone">Số điện thoại</label>
                <input type="text" id="twmp-combo-phone" name="phone" placeholder="0xxxxxxxxx" required />
                <button type="submit">Tra cứu</button>
            </form>
            <div id="twmp-combo-lookup-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_manage_shortcode( $atts ) {
        if ( ! is_user_logged_in() || ! self::current_user_is_administrator() ) {
            return '<p>Bạn không có quyền truy cập trang quản lý combo.</p>';
        }

        self::enqueue_assets();
        ob_start();
        ?>
        <div class="twmp-combo-manage">
            <div class="twmp-combo-actions">
                <form id="twmp-combo-create-form" class="twmp-combo-form" method="post">
                    <h3 style="font-weight: 700;font-size: 18px">Tạo combo mới</h3>
                    <label for="twmp-combo-customer-name">Tên khách</label>
                    <input type="text" id="twmp-combo-customer-name" name="customer_name" placeholder="Tên khách" required />
                    <label for="twmp-combo-phone">Số điện thoại</label>
                    <input type="text" id="twmp-combo-phone" name="phone" placeholder="0xxxxxxxxx" required />
                    <label for="twmp-combo-item-name">Tên món</label>
                    <input type="text" id="twmp-combo-item-name" name="item_name" placeholder="Tên món" />
                    <label for="twmp-combo-size">Loại combo</label>
                    <select id="twmp-combo-size" name="combo_size">
                        <option value="10">10 lượt</option>
                        <option value="20">20 lượt</option>
                        <option value="30">30 lượt</option>
                    </select>
                    <button type="submit">Tạo combo</button>
                </form>
                <form id="twmp-combo-search-form" class="twmp-combo-form" method="get">
                    <h3 style="font-weight: 700;font-size: 18px">Tìm combo</h3>
                    <label for="twmp-combo-search-query">Tên hoặc số điện thoại</label>
                    <input type="text" id="twmp-combo-search-query" name="query" placeholder="Tên hoặc số điện thoại" />
                    <button type="submit">Tìm</button>
                </form>
            </div>
            <div id="twmp-combo-manage-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function register_rest_routes() {
        register_rest_route( 'twmp-combo-cards/v1', '/lookup', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'rest_lookup' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'phone' => array(
                    'required' => true,
                ),
            ),
        ) );

        register_rest_route( 'twmp-combo-cards/v1', '/cards', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'rest_get_cards' ),
                'permission_callback' => array( __CLASS__, 'rest_permissions_check' ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'rest_create_card' ),
                'permission_callback' => array( __CLASS__, 'rest_permissions_check' ),
            ),
        ) );

        register_rest_route( 'twmp-combo-cards/v1', '/cards/(?P<id>\d+)', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'rest_update_card' ),
            'permission_callback' => array( __CLASS__, 'rest_permissions_check' ),
            'args'                => array(
                'id' => array('validate_callback' => 'is_numeric'),
            ),
        ) );

        register_rest_route( 'twmp-combo-cards/v1', '/cards/(?P<id>\d+)/slots/(?P<slot>\d+)', array(
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'rest_tick_slot' ),
                'permission_callback' => array( __CLASS__, 'rest_permissions_check' ),
            ),
            array(
                'methods'             => 'DELETE',
                'callback'            => array( __CLASS__, 'rest_untick_slot' ),
                'permission_callback' => array( __CLASS__, 'rest_permissions_check' ),
            ),
        ) );
    }

    public static function rest_permissions_check() {
        return is_user_logged_in() && self::current_user_can_manage();
    }

    public static function rest_lookup( WP_REST_Request $request ) {
        $phone = self::normalize_phone( $request->get_param( 'phone' ) );
        if ( empty( $phone ) ) {
            return new WP_Error( 'invalid_phone', 'Số điện thoại không hợp lệ.', array( 'status' => 400 ) );
        }

        $cards = self::get_cards_by_phone( $phone );
        if ( empty( $cards ) ) {
            return rest_ensure_response( array( 'cards' => array() ) );
        }

        return rest_ensure_response( array( 'cards' => $cards ) );
    }

    public static function rest_get_cards( WP_REST_Request $request ) {
        $query = $request->get_param( 'query' );
        $cards = self::search_cards( $query );
        return rest_ensure_response( array( 'cards' => $cards ) );
    }

    public static function rest_create_card( WP_REST_Request $request ) {
        $customer_name = sanitize_text_field( $request->get_param( 'customer_name' ) );
        $item_name = sanitize_text_field( $request->get_param( 'item_name' ) );
        $phone = self::normalize_phone( $request->get_param( 'phone' ) );
        $combo_size = intval( $request->get_param( 'combo_size' ) );

        if ( empty( $phone ) || empty( $customer_name ) ) {
            return new WP_Error( 'missing_fields', 'Tên khách và số điện thoại là bắt buộc.', array( 'status' => 400 ) );
        }

        if ( ! in_array( $combo_size, array( 10, 20, 30 ), true ) ) {
            return new WP_Error( 'invalid_combo_size', 'Loại combo phải là 10, 20 hoặc 30.', array( 'status' => 400 ) );
        }

        $created_at = current_time( 'mysql' );
        $purchased_at = $created_at;
        $expires_at = date( 'Y-m-d H:i:s', strtotime( '+1 month', current_time( 'timestamp' ) ) );

        global $wpdb;
        $table_name = $wpdb->prefix . 'twmp_combo_cards';

        $wpdb->insert(
            $table_name,
            array(
                'customer_name'    => $customer_name,
                'item_name'        => $item_name,
                'phone'            => $phone,
                'normalized_phone' => $phone,
                'combo_size'       => $combo_size,
                'purchased_at'     => $purchased_at,
                'expires_at'       => $expires_at,
                'status'           => 'active',
                'used_slots'       => wp_json_encode( array() ),
                'created_by'       => get_current_user_id(),
                'created_at'       => $created_at,
                'updated_at'       => $created_at,
            ),
            array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( $wpdb->insert_id ) {
            return rest_ensure_response( array( 'id' => $wpdb->insert_id ) );
        }

        return new WP_Error( 'db_error', 'Không thể tạo combo.', array( 'status' => 500 ) );
    }

    public static function rest_update_card( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $fields = array();
        $allowed = array( 'customer_name', 'item_name', 'phone', 'status' );

        foreach ( $allowed as $field ) {
            if ( null !== $request->get_param( $field ) ) {
                if ( 'phone' === $field ) {
                    $value = self::normalize_phone( $request->get_param( 'phone' ) );
                    if ( empty( $value ) ) {
                        return new WP_Error( 'invalid_phone', 'Số điện thoại không hợp lệ.', array( 'status' => 400 ) );
                    }
                    $fields['phone'] = $value;
                    $fields['normalized_phone'] = $value;
                } elseif ( 'status' === $field ) {
                    $status = sanitize_text_field( $request->get_param( 'status' ) );
                    if ( ! in_array( $status, array( 'active', 'completed', 'expired', 'cancelled' ), true ) ) {
                        return new WP_Error( 'invalid_status', 'Trạng thái không hợp lệ.', array( 'status' => 400 ) );
                    }
                    $fields['status'] = $status;
                } else {
                    $fields[ $field ] = sanitize_text_field( $request->get_param( $field ) );
                }
            }
        }

        if ( empty( $fields ) ) {
            return new WP_Error( 'no_changes', 'Không có dữ liệu để cập nhật.', array( 'status' => 400 ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'twmp_combo_cards';
        $fields['updated_at'] = current_time( 'mysql' );

        $updated = $wpdb->update( $table_name, $fields, array( 'id' => $id ), null, array( '%d' ) );

        if ( false === $updated ) {
            return new WP_Error( 'db_error', 'Cập nhật thất bại.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'updated' => true ) );
    }

    public static function rest_tick_slot( WP_REST_Request $request ) {
        return self::rest_modify_slot( $request, 'tick' );
    }

    public static function rest_untick_slot( WP_REST_Request $request ) {
        return self::rest_modify_slot( $request, 'untick' );
    }

    private static function rest_modify_slot( WP_REST_Request $request, $action ) {
        $id = absint( $request->get_param( 'id' ) );
        $slot = absint( $request->get_param( 'slot' ) );

        if ( $slot < 1 ) {
            return new WP_Error( 'invalid_slot', 'Số slot không hợp lệ.', array( 'status' => 400 ) );
        }

        $card = self::get_card_by_id( $id );
        if ( ! $card ) {
            return new WP_Error( 'not_found', 'Combo không tồn tại.', array( 'status' => 404 ) );
        }

        if ( $slot > $card['combo_size'] ) {
            return new WP_Error( 'invalid_slot', 'Slot vượt quá kích thước combo.', array( 'status' => 400 ) );
        }

        $used_slots = isset( $card['used_slots'] ) ? $card['used_slots'] : array();
        $next_slot = 1;
        while ( in_array( $next_slot, $used_slots, true ) ) {
            $next_slot++;
        }

        if ( 'tick' === $action ) {
            if ( in_array( $slot, $used_slots, true ) ) {
                return new WP_Error( 'already_tick', 'Slot này đã được tick.', array( 'status' => 400 ) );
            }

            if ( $slot !== $next_slot ) {
                return new WP_Error( 'invalid_order', 'Vui lòng tick theo thứ tự từ 1 đến số cuối cùng.', array( 'status' => 400 ) );
            }

            $used_slots[] = $slot;
            sort( $used_slots, SORT_NUMERIC );
        } else {
            if ( ! in_array( $slot, $used_slots, true ) ) {
                return new WP_Error( 'not_tick', 'Slot này chưa được tick.', array( 'status' => 400 ) );
            }

            $highest_slot = max( $used_slots );
            if ( $slot !== $highest_slot ) {
                return new WP_Error( 'invalid_order', 'Vui lòng bỏ tick theo thứ tự ngược lại, bắt đầu từ slot cao nhất.', array( 'status' => 400 ) );
            }

            $used_slots = array_values( array_diff( $used_slots, array( $slot ) ) );
        }

        $status = $card['status'];
        if ( 'cancelled' !== $status && 'expired' !== $status ) {
            if ( count( $used_slots ) >= $card['combo_size'] ) {
                $status = 'completed';
            } else {
                $status = 'active';
            }
        }

        if ( 'expired' !== $status && strtotime( $card['expires_at'] ) < current_time( 'timestamp' ) && 'cancelled' !== $status ) {
            $status = 'expired';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'twmp_combo_cards';
        $updated_at = current_time( 'mysql' );

        $wpdb->update(
            $table_name,
            array(
                'used_slots' => wp_json_encode( $used_slots ),
                'status'     => $status,
                'updated_at' => $updated_at,
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        self::log_slot_action( $id, $slot, $action );

        return rest_ensure_response( array( 'updated' => true, 'used_slots' => $used_slots, 'status' => $status ) );
    }

    public static function get_cards_by_phone( $phone ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twmp_combo_cards';
        $normalized_phone = self::normalize_phone( $phone );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE normalized_phone = %s ORDER BY purchased_at DESC", $normalized_phone ), ARRAY_A );

        if ( empty( $rows ) ) {
            return array();
        }

        return array_map( array( __CLASS__, 'prepare_card' ), $rows );
    }

    public static function search_cards( $query ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twmp_combo_cards';
        $sql = "SELECT * FROM {$table_name}";
        $where = array();
        $params = array();

        if ( ! empty( $query ) ) {
            $normalized_phone = self::normalize_phone( $query );
            if ( $normalized_phone ) {
                $where[] = 'normalized_phone = %s';
                $params[] = $normalized_phone;
            } else {
                $where[] = '(customer_name LIKE %s OR item_name LIKE %s OR phone LIKE %s)';
                $params[] = '%' . $wpdb->esc_like( $query ) . '%';
                $params[] = '%' . $wpdb->esc_like( $query ) . '%';
                $params[] = '%' . $wpdb->esc_like( $query ) . '%';
            }
        }

        if ( ! empty( $where ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }
        $sql .= ' ORDER BY purchased_at DESC';

        $prepared_sql = empty( $params ) ? $sql : $wpdb->prepare( $sql, $params );
        $rows = $wpdb->get_results( $prepared_sql, ARRAY_A );
        $cards = array();
        foreach ( $rows as $row ) {
            $cards[] = self::prepare_card( $row, true );
        }
        return $cards;
    }

    public static function get_card_by_id( $id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'twmp_combo_cards';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ), ARRAY_A );
        return $row ? self::prepare_card( $row ) : false;
    }

    public static function prepare_card( $row, $include_logs = false ) {
        $row['used_slots'] = $row['used_slots'] ? json_decode( $row['used_slots'], true ) : array();
        if ( ! is_array( $row['used_slots'] ) ) {
            $row['used_slots'] = array();
        }

        if ( strtotime( $row['expires_at'] ) < current_time( 'timestamp' ) && ! in_array( $row['status'], array( 'completed', 'cancelled' ), true ) ) {
            $row['status'] = 'expired';
        }

        if ( $include_logs ) {
            $row['logs'] = self::get_card_logs( $row['id'] );
        }

        return $row;
    }

    public static function get_card_logs( $card_id ) {
        global $wpdb;
        $log_table = $wpdb->prefix . 'twmp_combo_card_logs';
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$log_table} WHERE card_id = %d ORDER BY performed_at DESC", $card_id ), ARRAY_A );
        if ( empty( $rows ) ) {
            return array();
        }

        foreach ( $rows as &$row ) {
            $user = $row['performed_by'] ? get_userdata( $row['performed_by'] ) : false;
            $row['performed_by_name'] = $user ? $user->display_name : 'Không rõ';
        }

        return $rows;
    }

    public static function log_slot_action( $card_id, $slot, $action ) {
        global $wpdb;
        $log_table = $wpdb->prefix . 'twmp_combo_card_logs';
        $wpdb->insert(
            $log_table,
            array(
                'card_id'      => $card_id,
                'slot'         => $slot,
                'action'       => $action,
                'performed_by' => get_current_user_id(),
                'performed_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%d', '%s' )
        );
    }

    public static function normalize_phone( $phone ) {
        if ( empty( $phone ) ) {
            return '';
        }
        $normalized = preg_replace( '/[^0-9+]/', '', $phone );
        $normalized = trim( $normalized );
        if ( strpos( $normalized, '+84' ) === 0 ) {
            $normalized = '0' . substr( $normalized, 3 );
        }
        if ( strpos( $normalized, '+') === 0 ) {
            $normalized = ltrim( $normalized, '+' );
        }
        return $normalized;
    }

    public static function current_user_can_manage() {
        return current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_shop_orders' );
    }

    private static function table_exists( $table ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
    }

    private static function table_has_column( $table, $column ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ) === $column;
    }

    public static function current_user_is_administrator() {
        return current_user_can( 'administrator' );
    }
}
