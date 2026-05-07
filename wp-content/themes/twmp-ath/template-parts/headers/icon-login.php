<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args($args, [
	'class' => '',
]);

?>

<div class="header-main__item-login">
    <a href="#">
        <span class="header-main__item-login__icon"><?php echo twmp_get_svg_icon('unlock'); ?></span>
        <span class="header-main__item-login__text"><?php echo esc_html__('Log In', 'twmp-ath') ?></span>
    </a>
</div>