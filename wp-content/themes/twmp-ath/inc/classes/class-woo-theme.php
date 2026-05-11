<?php

namespace TWMP_THEME\Inc;

use TWMP_THEME\Inc\Traits\Singleton;

class Woo_Theme
{

    use Singleton;

    /**
     * Construct method.
     */
    protected function __construct()
    {
        $this->setup_hooks();
    }

    /**
     * To register action/filter.
     *
     * @return void
     */
    protected function setup_hooks()
    {
        require_once get_theme_file_path('/inc/woocommerces/helpers/helper.php');
        // require_once get_theme_file_path('/inc/woocommerces/helpers/artists.php');
        require_once get_theme_file_path('/inc/woocommerces/disable.php');
        require_once get_theme_file_path('/inc/woocommerces/global.php');
        // require_once get_theme_file_path('/inc/woocommerces/single.php');
        require_once get_theme_file_path('/inc/woocommerces/archive.php');
        // require_once get_theme_file_path('/inc/woocommerces/cart.php');
        require_once get_theme_file_path('/inc/woocommerces/checkout.php');
        require_once get_theme_file_path('/inc/woocommerces/thank-you.php');
        // require_once get_theme_file_path('/inc/woocommerces/account.php');
    }
}
