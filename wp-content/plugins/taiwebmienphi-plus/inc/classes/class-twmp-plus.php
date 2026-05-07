<?php

namespace TWMP_PLUS\Inc;

use TWMP_PLUS\Inc\Traits\Singleton;

class TWMP_PLUS {
    use Singleton;

    protected function __construct() {
        // REGISTER_BLOCKS::get_instance();
        TWMP_PLUS_SHORTCODE::get_instance();
        $this->setup_hooks();
    }

    protected function setup_hooks() {
    }

    public function setup_theme() {}
}
