<?php

namespace TWMP_PLUS\Inc;

use TWMP_PLUS\Inc\Traits\Singleton;

class TWMP_PLUS_UNINSTALL
{
    use Singleton;

    protected function __construct()
    {
        $this->setup_hooks();
    }

    protected function setup_hooks()
    {

    }

    public static function twmp_plus_register_uninstall() {
      
    }
}
