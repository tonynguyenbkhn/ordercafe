<?php
/**
 * Plugin Name: TWMP Combo Cards
 * Description: Quản lý combo prepaid 10/20/30 lượt và hiển thị lookup công khai theo số điện thoại.
 * Version: 1.0.3
 * Author: OrderCafe
 * Text Domain: twmp-combo-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TWMP_COMBO_CARDS_VERSION', '1.0.3' );
define( 'TWMP_COMBO_CARDS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TWMP_COMBO_CARDS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TWMP_COMBO_CARDS_DB_VERSION', '1.0.1' );

require_once TWMP_COMBO_CARDS_PLUGIN_DIR . 'includes/class-twmp-combo-cards.php';

register_activation_hook( __FILE__, array( 'TWMP_Combo_Cards', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TWMP_Combo_Cards', 'deactivate' ) );

TWMP_Combo_Cards::init();
