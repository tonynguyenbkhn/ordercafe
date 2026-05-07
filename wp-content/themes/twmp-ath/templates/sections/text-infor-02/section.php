<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args(
    $args,
    [
        'id'               => '',
        'class'            => '',
        'class_container'  => '',
        'title'            => '',
        'description'      => '',
        'items'            => [],
        'enable_container' => false,
    ]
);

$_class = 'text-infor-02';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_class_container = 'container';
$_class_container .= !empty($data['class_container']) ? esc_attr(' ' . $data['class_container']) : '';

$items = is_array($data['items']) ? array_values(array_filter($data['items'])) : [];
?>

<section class="<?php echo esc_attr($_class); ?>"<?php if (! empty($data['id'])) : ?> id="<?php echo esc_attr($data['id']); ?>"<?php endif; ?>>
    <?php if ($data['enable_container']) : ?>
        <div class="<?php echo esc_attr($_class_container); ?>">
        <?php endif; ?>

        <div class="text-infor-02__shell">
            <?php
            get_template_part('templates/components/heading', null, [
                'title_class' => 'text-infor-02__title',
                'description_class' => 'text-infor-02__description',
                'class' => 'text-infor-02__heading flex-column',
                'title' => ! empty($data['title']) ? $data['title'] : '',
                'description' => ! empty($data['description']) ? $data['description'] : '',
            ]);
            ?>

            <?php if (! empty($items)) : ?>
                <div class="text-infor-02__items">
                    <?php foreach ($items as $index => $item) : ?>
                        <?php
                        get_template_part('templates/sections/text-infor-02/item', null, [
                            'class' => 'text-infor-02__item',
                            'index' => $index,
                            'title' => isset($item['title']) ? $item['title'] : '',
                            'description' => isset($item['description']) ? $item['description'] : '',
                            'button_text' => isset($item['button_text']) ? $item['button_text'] : '',
                            'button_link' => isset($item['button_link']) ? $item['button_link'] : '',
                        ]);
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($data['enable_container']) : ?>
        </div>
    <?php endif; ?>
</section>
