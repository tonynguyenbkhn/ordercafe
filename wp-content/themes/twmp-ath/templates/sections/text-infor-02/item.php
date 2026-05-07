<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args(
    $args,
    [
        'class'        => '',
        'index'        => 0,
        'title'        => '',
        'description'  => '',
        'button_text'  => '',
        'button_link'  => '',
    ]
);

$title = is_string($data['title']) ? trim($data['title']) : '';
$description = is_string($data['description']) ? trim($data['description']) : '';
$button_text = is_string($data['button_text']) ? trim($data['button_text']) : '';
$button_link = is_string($data['button_link']) ? trim($data['button_link']) : '';

if ('' === $title && '' === $description && '' === $button_text) {
    return;
}

$_class = 'text-infor-02__item';
$_class .= ! empty($data['class']) ? esc_attr(' ' . $data['class']) : '';
$_number = (int) $data['index'] + 1;
?>

<article class="<?php echo esc_attr($_class); ?>">
    <div class="text-infor-02__item-number" aria-hidden="true"><?php echo esc_html((string) $_number); ?></div>

    <?php if ('' !== $title) : ?>
        <h3 class="text-infor-02__item-title"><?php echo esc_html($title); ?></h3>
    <?php endif; ?>

    <?php if ('' !== $description) : ?>
        <div class="text-infor-02__item-description">
            <?php echo wp_kses_post($description); ?>
        </div>
    <?php endif; ?>

    <?php if ('' !== $button_text && '' !== $button_link) : ?>
        <div class="text-infor-02__item-actions">
            <?php
            get_template_part(
                'templates/components/button',
                null,
                [
                    'class'              => 'text-infor-02__button button-normal typo-system-button',
                    'button_text'        => $button_text,
                    'button_url'         => $button_link,
                    'button_link_target' => '_self',
                ]
            );
            ?>
        </div>
    <?php endif; ?>
</article>
