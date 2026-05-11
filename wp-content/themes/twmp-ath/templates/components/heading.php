<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args($args, [
    'title_class' => '',
    'description_class' => '',
    'class' => '',
    'title' => '',
    'description' => '',
    'link' => ''
]);

$_class = 'heading';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_title_class = 'heading__title';
$_title_class .= !empty($data['title_class']) ? esc_attr(' ' . $data['title_class']) : '';

$_description_class = 'heading__description';
$_description_class .= !empty($data['description_class']) ? esc_attr(' ' . $data['description_class']) : '';

?>


<div class="<?php echo esc_attr($_class) ?>">
    <?php if (!empty($data['title'])) : ?>
        <h2 <?php echo !empty($data['link']) ? '' : 'class="' . esc_attr($_title_class) . '"' ?>>
            <?php if (!empty($data['link'])) : ?><a class="<?php echo esc_attr($_title_class); ?>" href="<?php echo esc_url($data['link']); ?>"><?php endif; ?>
                <?php echo esc_html($data['title']); ?>
                <?php if (!empty($data['link'])) : ?></a><?php endif; ?>
        </h2>
    <?php endif; ?>
    <?php if (!empty($data['description'])) : ?>
        <div class="<?php echo esc_attr($_description_class); ?>">
            <?php echo wp_kses_post($data['description']); ?>
        </div>
    <?php endif; ?>
</div>