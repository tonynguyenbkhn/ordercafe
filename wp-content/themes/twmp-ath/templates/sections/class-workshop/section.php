<?php

if (! defined('ABSPATH')) {
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
        'button_text'      => '',
        'button_link'      => '',
        'products'         => [],
        'enable_container' => false,
    ]
);

$_class = 'class-section';
$_class .= ! empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_class_container = 'container';
$_class_container .= ! empty($data['class_container']) ? esc_attr(' ' . $data['class_container']) : '';

$product_ids = is_array($data['products']) ? array_values(array_filter(array_map('absint', $data['products']))) : [];
$slides = [];
$palette = [
    'class-card--theme-red',
    'class-card--theme-orange',
    'class-card--theme-purple',
    'class-card--theme-dark',
];

foreach ($product_ids as $index => $product_id) {
    $product_post = get_post($product_id);

    if (! $product_post instanceof WP_Post || 'publish' !== $product_post->post_status) {
        continue;
    }

    $badges = function_exists('get_field') ? get_field('ath_badges', $product_id) : [];
    $short_info = function_exists('get_field') ? (string) get_field('ath_short_info', $product_id) : '';
    $location_detail = function_exists('get_field') ? (string) get_field('ath_location_detail', $product_id) : '';
    $location = function_exists('get_field') ? (string) get_field('ath_location', $product_id) : '';
    $description_source = trim(wp_strip_all_tags((string) $product_post->post_content));
    $description = '';

    if ('' !== $description_source) {
        $description = wp_trim_words($description_source, 18, '...');
    } elseif (! empty($product_post->post_excerpt)) {
        $description = wp_trim_words(wp_strip_all_tags($product_post->post_excerpt), 18, '...');
    }

    $timestamp = get_post_timestamp($product_post);
    $date_day = $timestamp ? wp_date('j', $timestamp) : '';
    $date_weekday = $timestamp ? strtoupper(wp_date('D', $timestamp)) : '';
    $date_month = $timestamp ? strtoupper(wp_date('M', $timestamp)) : '';
    $date_year = $timestamp ? wp_date('y', $timestamp) : '';

    ob_start();
    get_template_part(
        'templates/sections/class-workshop/item',
        null,
        [
            'product_id'     => $product_id,
            'title'          => get_the_title($product_post),
            'badges'         => is_array($badges) ? $badges : [],
            'short_info'     => $short_info,
            'location'       => trim($location_detail) ? $location_detail : $location,
            'description'    => $description,
            'date_day'       => $date_day,
            'date_weekday'   => $date_weekday,
            'date_month'     => $date_month,
            'date_year'      => $date_year,
            'image_id'       => get_post_thumbnail_id($product_id),
            'image_size'     => 'large',
            'lazyload'       => true,
            'permalink'      => get_permalink($product_id),
            'featured'       => 0 === $index,
            'theme_class'    => $palette[$index % count($palette)],
        ]
    );
    $slides[] = [
        'content' => ob_get_clean(),
        'class'   => 'class-section__slide',
    ];
}

$has_intro = ! empty($data['title']) || ! empty($data['description']) || (! empty($data['button_text']) && ! empty($data['button_link']));

if (! $has_intro && empty($slides)) {
    return;
}
?>

<section class="<?php echo esc_attr($_class); ?>" <?php if (! empty($data['id'])) : ?> id="<?php echo esc_attr(sanitize_file_name(strtolower($data['id']))); ?>" <?php endif; ?>>
    <?php if ($data['enable_container']) : ?>
        <div class="<?php echo esc_attr($_class_container); ?>">
        <?php endif; ?>

        <div class="class-section__shell">

            <?php if (! empty($slides)) : ?>
                <div class="class-section__slider-wrap position-relative">
                    <div class="class-section__slider">
                        <div class="swiper-button swiper-button-prev">

                        </div>
                        <div class="swiper-button swiper-button-next">

                        </div>
                        <?php
                        get_template_part(
                            'templates/components/swiper',
                            null,
                            [
                                'class'            => 'class-section__swiper',
                                'data_block'       => 'class-workshop',
                                'enable_container' => false,
                                'settings'         => [
                                    'autoPlay'        => false,
                                    'pagination'      => [
                                        'el'        => '.swiper-pagination',
                                        'type'      => 'progressbar',
                                        'clickable' => false,
                                    ],
                                    'prevNextButtons' => false,
                                    'slidesPerView'   => 1.15,
                                    'spaceBetween'    => 32,
                                    'breakpoints'     => [
                                        640  => [
                                            'slidesPerView' => 1.4,
                                            'spaceBetween'  => 32,
                                        ],
                                        992  => [
                                            'slidesPerView' => 2.3,
                                            'spaceBetween'  => 32,
                                        ],
                                        1200 => [
                                            'slidesPerView' => 1.8,
                                            'spaceBetween'  => 0
                                        ],
                                    ],
                                ],
                                'items'            => $slides,
                            ]
                        );
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="class-section__intro-row">
                <div class="class-section__intro-row-wrapper">
                    <div class="class-section__intro">
                        <?php
                        get_template_part(
                            'templates/components/heading',
                            null,
                            [
                                'title_class'       => 'class-section__title',
                                'description_class' => 'class-section__description',
                                'class'             => 'class-section__heading flex-column',
                                'title'             => $data['title'],
                                'description'       => $data['description']
                            ]
                        );
                        ?>
                    </div>
                    <div class="class-section__actions">
                        <?php if (! empty($data['button_text']) && ! empty($data['button_link'])) : ?>

                            <?php
                            get_template_part(
                                'templates/components/button',
                                null,
                                [
                                    'class'              => 'class-section__button button-medium typo-system-button',
                                    'button_text'        => $data['button_text'],
                                    'button_url'         => $data['button_link'],
                                    'button_link_target' => '_self',
                                    'svg_icon_after'     => twmp_get_svg_icon('arrow-right'),
                                ]
                            );
                            ?>
                    </div>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <?php if ($data['enable_container']) : ?>
        </div>
    <?php endif; ?>
</section>