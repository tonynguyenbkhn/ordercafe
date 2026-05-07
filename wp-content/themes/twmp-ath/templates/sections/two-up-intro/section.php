<?php

if (! defined('ABSPATH')) {
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
        'image_id'              => 0,
        'secondary_image_id'    => 0,
        'image_size'            => 'full',
        'secondary_image_size'  => 'full',
        'lazyload'              => false,
        'enable_container'      => false,
        'counters'              => [],
        'primary_button_text'   => '',
        'primary_button_link'   => '',
        'secondary_button_text' => '',
        'secondary_button_link' => '',
    ]
);

$_class = 'two-up-intro';
$_class .= ! empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_class_container = 'container';
$_class_container .= ! empty($data['class_container']) ? esc_attr(' ' . $data['class_container']) : '';

$has_primary_button = ! empty($data['primary_button_text']) && ! empty($data['primary_button_link']);
$has_secondary_button = ! empty($data['secondary_button_text']) && ! empty($data['secondary_button_link']);
$has_buttons = $has_primary_button || $has_secondary_button;

?>

<section class="<?php echo esc_attr($_class); ?>" <?php if (! empty($data['id'])) : ?> id="<?php echo esc_attr($data['id']); ?>" <?php endif; ?>>
    <?php if ($data['enable_container']) : ?>
        <div class="<?php echo esc_attr($_class_container); ?>">
        <?php endif; ?>

        <div class="two-up-intro__grid">
            <div class="two-up-intro__content">

                <?php
                get_template_part('templates/components/description', null, [
                    'description_class' => '',
                    'class' => 'two-up-intro__header flex-column',
                    'description' => $data && !empty($data['description']) ? $data['description'] : '',
                ]);
                ?>

                <?php if ($has_buttons) : ?>
                    <div class="two-up-intro__actions">
                        <?php
                        if ($has_primary_button) {
                            get_template_part(
                                'templates/components/button',
                                null,
                                [
                                    'class'              => 'two-up-intro__button two-up-intro__button--primary bg-primary-500 text-system-white typo-system-button button-medium',
                                    'button_text'        => $data['primary_button_text'],
                                    'button_url'         => $data['primary_button_link'],
                                    'button_link_target' => '_self',
                                ]
                            );
                        }

                        if ($has_secondary_button) {
                            get_template_part(
                                'templates/components/button',
                                null,
                                [
                                    'class'              => 'two-up-intro__button two-up-intro__button--secondary text-system-white typo-system-button button-medium',
                                    'button_text'        => $data['secondary_button_text'],
                                    'button_url'         => $data['secondary_button_link'],
                                    'button_link_target' => '_self',
                                ]
                            );
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="two-up-intro__media">
                <div class="two-up-intro__media-accent" aria-hidden="true"></div>

                <?php if (! empty($data['image_id'])) : ?>
                    <div class="two-up-intro__media-primary">
                        <?php
                        get_template_part(
                            'templates/components/image',
                            null,
                            [
                                'image_id'    => $data['image_id'],
                                'image_size'  => $data['image_size'],
                                'lazyload'    => $data['lazyload'],
                                'class'       => 'two-up-intro__image-wrap two-up-intro__image-wrap--primary image--cover image--default',
                                'image_class' => 'two-up-intro__image two-up-intro__image--primary',
                                'alt'         => $data['title'],
                            ]
                        );
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (! empty($data['secondary_image_id'])) : ?>
                    <div class="two-up-intro__media-secondary">
                        <?php
                        get_template_part(
                            'templates/components/image',
                            null,
                            [
                                'image_id'    => $data['secondary_image_id'],
                                'image_size'  => $data['secondary_image_size'],
                                'lazyload'    => $data['lazyload'],
                                'class'       => 'two-up-intro__image-wrap two-up-intro__image-wrap--secondary image--cover image--default',
                                'image_class' => 'two-up-intro__image two-up-intro__image--secondary',
                                'alt'         => $data['title'],
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