<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args($args, []);

$_class = '';
$_class .= !empty($data['class']) ? esc_attr($data['class']) : '';

$_col_class = 'post-grid__col';
$_col_class .= !empty($data['col_class']) ? esc_attr(' ' . $data['col_class']) : '';

?>

<div class="<?php echo esc_attr( $_class ); ?>" data-open-modal="modal-search-form">
    <span class="header__menu-icons__icon header__menu-icons__icon-search">
        <?php echo twmp_get_svg_icon('search') ?>
    </span>
    <span class="screen-reader-text"><?php echo esc_html__('Open a search form', 'twmp-ath') ?></span>
</div>