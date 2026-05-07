<?php

namespace TWMP_PLUS\Inc;

use TWMP_PLUS\Inc\Traits\Singleton;

class TWMP_PLUS_ACTIVATION
{
    use Singleton;

    protected function __construct()
    {
        $this->setup_hooks();
    }

    protected function setup_hooks()
    {

    }

    public function twmp_plus_register_activation() {
        
    }
}
