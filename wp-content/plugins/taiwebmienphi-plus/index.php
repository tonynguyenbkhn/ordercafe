<?php
/**
 * Plugin Name:       Taiwebmienphi Plus
 * Plugin URI:        https://taiwebmienphi.com/plugins/taiwewbmienphi-plus
 * Description:       A plugin for adding blocks to a theme.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.2
 * Author:            taiwebmienphi
 * Author URI:        https://taiwebmienphi.com/taiwebmienphi
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       taiwebmienphi-plus
 * Domain Path:       /languages
 */

 if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

define('TWMP_PLUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TWMP_PLUS_PLUGIN_FILE', __FILE__);

require_once TWMP_PLUS_PLUGIN_DIR . '/inc/helpers/helpers.php';
require_once TWMP_PLUS_PLUGIN_DIR . '/inc/helpers/utility.php';
require_once TWMP_PLUS_PLUGIN_DIR . '/inc/helpers/autoloader.php';

function twmp_plus_get_plugin_instance() {
    \TWMP_PLUS\Inc\TWMP_PLUS::get_instance();
}

twmp_plus_get_plugin_instance();

use TWMP_PLUS\Inc\TWMP_PLUS_ACTIVATION;
use TWMP_PLUS\Inc\TWMP_PLUS_DEACTIVATION;
use TWMP_PLUS\Inc\TWMP_PLUS_UNINSTALL;

register_activation_hook( __FILE__, array( TWMP_PLUS_ACTIVATION::get_instance(), 'twmp_plus_register_activation' ) );
register_deactivation_hook( __FILE__, array( TWMP_PLUS_DEACTIVATION::get_instance(), 'twmp_plus_register_deactivation' ) );
register_uninstall_hook( __FILE__, array( 'TWMP_PLUS_UNINSTALL', 'twmp_plus_plugin_uninstall' ) );