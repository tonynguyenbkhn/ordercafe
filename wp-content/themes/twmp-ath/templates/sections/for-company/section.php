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

$_class = 'for-company';
$_class .= ! empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_class_container = 'container';
$_class_container .= ! empty($data['class_container']) ? esc_attr(' ' . $data['class_container']) : '';

$has_primary_button = ! empty($data['primary_button_text']) && ! empty($data['primary_button_link']);
$has_secondary_button = ! empty($data['secondary_button_text']) && ! empty($data['secondary_button_link']);
$has_buttons = $has_primary_button || $has_secondary_button;
$counters = is_array($data['counters']) ? array_filter($data['counters']) : [];
?>

<section class="<?php echo esc_attr($_class); ?>" <?php if (! empty($data['id'])) : ?> id="<?php echo esc_attr($data['id']); ?>" <?php endif; ?>>
    <div class="for-company__light">
        <img width="1052px" height="816px" src="<?php echo esc_url(TWMP_IMG_URI . '/event-light.png'); ?>" alt="<?php echo esc_attr__('Our service', 'twmp-ath'); ?>">
    </div>
    <?php if ($data['enable_container']) : ?>
        <div class="<?php echo esc_attr($_class_container); ?>">
        <?php endif; ?>

        <div class="for-company__grid">

            <div class="for-company__media">
                <div class="for-company__media-accent" aria-hidden="true"></div>

                <?php if (! empty($data['image_id'])) : ?>
                    <div class="for-company__media-primary">
                        <?php
                        get_template_part(
                            'templates/components/image',
                            null,
                            [
                                'image_id'    => $data['image_id'],
                                'image_size'  => $data['image_size'],
                                'lazyload'    => $data['lazyload'],
                                'class'       => 'for-company__image-wrap for-company__image-wrap--primary image--cover image--default',
                                'image_class' => 'for-company__image for-company__image--primary',
                                'alt'         => $data['title'],
                            ]
                        );
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (! empty($data['secondary_image_id'])) : ?>
                    <div class="for-company__media-secondary">
                        <?php
                        get_template_part(
                            'templates/components/image',
                            null,
                            [
                                'image_id'    => $data['secondary_image_id'],
                                'image_size'  => $data['secondary_image_size'],
                                'lazyload'    => $data['lazyload'],
                                'class'       => 'for-company__image-wrap for-company__image-wrap--secondary image--cover image--default',
                                'image_class' => 'for-company__image for-company__image--secondary',
                                'alt'         => $data['title'],
                            ]
                        );
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="for-company__content">
                <div class="for-company__content-wrap">
                    <?php
                    get_template_part('templates/components/heading', null, [
                        'title_class' => 'for-company__title',
                        'description_class' => 'for-company__description',
                        'class' => 'for-company__header flex-column',
                        'title' => $data && !empty($data['title']) ? $data['title'] : '',
                        'description' => $data && !empty($data['description']) ? $data['description'] : '',
                    ]);
                    ?>

                    <?php if (! empty($counters)) : ?>
                        <div class="for-company__stats" data-block="about-couter" role="list">
                            <?php foreach ($counters as $counter) : ?>
                                <?php
                                $value = isset($counter['value']) ? trim((string) $counter['value']) : '';
                                $label = isset($counter['label']) ? trim((string) $counter['label']) : '';

                                if ('' === $value && '' === $label) {
                                    continue;
                                }
                                ?>
                                <div class="for-company__stat" role="listitem">
                                    <?php if ('' !== $value) : ?>
                                        <div class="for-company__stat-value"><?php echo esc_html($value); ?></div>
                                    <?php endif; ?>
                                    <?php if ('' !== $label) : ?>
                                        <div class="for-company__stat-label"><?php echo esc_html($label); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($has_buttons) : ?>
                        <div class="for-company__actions">
                            <?php
                            if ($has_primary_button) {
                                get_template_part(
                                    'templates/components/button',
                                    null,
                                    [
                                        'class'              => 'for-company__button for-company__button--primary bg-primary-500 text-system-white typo-system-button button-medium',
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
                                        'class'              => 'for-company__button for-company__button--secondary text-system-white typo-system-button button-medium',
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
            </div>
        </div>

        <?php if ($data['enable_container']) : ?>
        </div>
    <?php endif; ?>
</section>