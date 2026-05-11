<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args($args, [
    'class' => '',
    'id' => '',
    'attributes' => '',
    'close_button_class' => ''
]);

$_class = 'modal modal--search-form';
$_class .= !empty($data['class']) ? ' ' . $data['class'] : '';

$_id = !empty($data['id']) ? $data['id'] : '';
$_custom_attributes = !empty($data['attributes']) ? $data['attributes'] : '';

$_close_button_class = !empty($data['close_button_class']) ? $data['close_button_class'] : 'js-close-button';
?>

<div class="<?php echo esc_attr($_class); ?>" id="<?php echo esc_attr($_id); ?>" role="dialog" <?php echo esc_attr($_custom_attributes); ?> data-block="modal-search-form">
    <div class="modal__wrapper">
        <div class="modal__header">
            <span class="modal__title"><?php esc_html_e('Type to search', 'twmp-ath'); ?></span>

        </div>
        <div class="modal__content js-content">
            <button class="modal__close-button" data-close-modal="modal-search-form" aria-label="<?php echo esc_attr__('Close a search form modal', 'twmp-ath'); ?>">
                <span class="typo-text-md-medium text-system-content-2"><?php echo esc_html__('Cancel', 'twmp-ath'); ?></span>
            </button>
            <?php
            get_product_search_form();
            ?>
        </div>
        <button class="modal__close-button <?php echo esc_attr($_close_button_class); ?>" data-close-modal="modal-search-form" aria-label="<?php esc_attr_e('Close a modal', 'twmp-ath'); ?>">
            <?php echo twmp_get_svg_icon('close'); ?>
        </button>
    </div>
</div>