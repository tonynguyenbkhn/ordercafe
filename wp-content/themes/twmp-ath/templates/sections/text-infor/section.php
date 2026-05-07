<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args(
    $args,
    [
        'id'                    => '',
        'class'                 => '',
        'class_container'       => '',
        'title'                 => '',
        'description'           => '',
        'text-1'                => '',
        'text-2'                => '',
        'primary_button_text'   => '',
        'primary_button_link'   => '',
        'enable_container'      => false,
    ]
);

$_class = 'text-infor';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_class_container = 'container';
$_class_container .= !empty($data['class_container']) ? esc_attr(' ' . $data['class_container']) : '';

$has_primary_button = ! empty($data['primary_button_text']) && ! empty($data['primary_button_link']);
?>

<section class="<?php echo esc_attr($_class); ?>" data-block="text-infor"<?php if (! empty($data['id'])) : ?> id="<?php echo esc_attr($data['id']); ?>"<?php endif; ?>>
    <?php if ($data['enable_container']) : ?>
        <div class="<?php echo esc_attr($_class_container); ?>">
        <?php endif; ?>

        <div class="text-infor__shell">
            <?php
            get_template_part('templates/components/heading', null, [
                'title_class' => 'text-infor__title',
                'class' => 'text-infor__heading flex-column',
                'title' => ! empty($data['title']) ? $data['title'] : '',
            ]);
            ?>

            <div class="text-infor__panel">
                <?php if (! empty($data['description'])) : ?>
                    <div class="text-infor__description">
                        <?php echo wp_kses_post($data['description']); ?>
                    </div>
                <?php endif; ?>

                <div class="text-infor__columns">
                    <?php
                    get_template_part('templates/sections/text-infor/item', null, [
                        'class' => 'text-infor__column text-infor__column--left',
                        'content' => ! empty($data['text-1']) ? $data['text-1'] : '',
                    ]);

                    get_template_part('templates/sections/text-infor/item', null, [
                        'class' => 'text-infor__column text-infor__column--right',
                        'content' => ! empty($data['text-2']) ? $data['text-2'] : '',
                    ]);
                    ?>
                </div>

                <?php if ($has_primary_button) : ?>
                    <div class="text-infor__actions">
                        <?php
                        get_template_part(
                            'templates/components/button',
                            null,
                            [
                                'class'              => 'text-infor__button bg-primary-500 text-system-white typo-system-button button-medium',
                                'button_text'        => $data['primary_button_text'],
                                'button_url'         => $data['primary_button_link'],
                                'button_link_target' => '_self',
                            ]
                        );
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($data['enable_container']) : ?>
        </div>
    <?php endif; ?>
</section>
