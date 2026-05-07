<?php

if (! defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args(
    $args,
    [
        'id' => '',
        'class' => '',
        'class_container' => '',
        'title' => '',
        'description' => '',
        'background_image_id' => 0,
        'enable_container' => true,
    ]
);

$_class = 'contact-us section';
$_class .= ! empty($data['class']) ? ' ' . sanitize_html_class($data['class']) : '';

$_class_container = 'container';
$_class_container .= ! empty($data['class_container']) ? ' ' . sanitize_html_class($data['class_container']) : '';

$address = function_exists('get_field') ? (string) get_field('address', 'option') : '';
$hotline = function_exists('get_field') ? (string) get_field('hotline', 'option') : '';

$social_links = function_exists('get_field') ? get_field('social_links', 'option') : [];
$sticky_links = function_exists('get_field') ? get_field('sticky_links', 'option') : [];

// $social_links = is_array($social_links) ? $social_links : [];
// $sticky_links = is_array($sticky_links) ? $sticky_links : [];

// $find_link_by_type = static function (array $links, $target_type) {
//     foreach ($links as $link) {
//         $type = isset($link['type']) ? (string) $link['type'] : '';
//         $url = isset($link['url']) ? (string) $link['url'] : '';
//         if ($type === $target_type && '' !== trim($url)) {
//             return $url;
//         }
//     }

//     return '';
// };

$tiktok_url = function_exists('get_field') ? (string) get_field('tiktok', 'option') : '';
$facebook_url = function_exists('get_field') ? (string) get_field('facebook', 'option') : '';
$zalo_url = function_exists('get_field') ? (string) get_field('zalo', 'option') : '';

$social_items = [
    [
        'icon' => 'contact-section-tiktok',
        'url' => $tiktok_url,
        'label' => 'TikTok',
    ],
    [
        'icon' => 'contact-section-facebook',
        'url' => $facebook_url,
        'label' => 'Facebook',
    ],
    [
        'icon' => 'contact-section-zalo',
        'url' => $zalo_url,
        'label' => 'Zalo',
    ],
];

$social_items = array_values(
    array_filter(
        $social_items,
        static function ($item) {
            return ! empty($item['url']);
        }
    )
);

$background_image_url = ! empty($data['background_image_id']) ? wp_get_attachment_image_url((int) $data['background_image_id'], 'full') : '';
?>

<section class="<?php echo esc_attr($_class); ?>" <?php if (! empty($data['id'])) : ?> id="<?php echo esc_attr($data['id']); ?>" <?php endif; ?> <?php if ($background_image_url) : ?> style="background-image: url('<?php echo esc_url($background_image_url); ?>');" <?php endif; ?>>
    <?php if ($data['enable_container']) : ?>
        <div class="<?php echo esc_attr($_class_container); ?>">
        <?php endif; ?>

        <div class="contact-us__box">
            <div class="contact-us__inner">
                <div class="contact-us__col contact-us__col--info">

                    <?php
                    get_template_part('templates/components/heading', null, [
                        'title_class' => 'contact-us__title',
                        'description_class' => 'contact-us__description',
                        'class' => 'contact-us__header flex-column',
                        'title' => $data && !empty($data['title']) ? $data['title'] : '',
                        'description' => $data && !empty($data['description']) ? $data['description'] : '',
                    ]);
                    ?>

                    <?php if ('' !== trim($address)) : ?>
                        <div class="contact-us__meta-item contact-us__meta-item--address">
                            <span class="contact-us__meta-icon" aria-hidden="true"><?php echo twmp_get_svg_icon('contact-section-pin'); ?></span>
                            <span class="contact-us__meta-text"><?php echo esc_html($address); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ('' !== trim($hotline)) : ?>
                        <div class="contact-us__meta-item contact-us__meta-item--hotline">
                            <span class="contact-us__meta-icon" aria-hidden="true"><?php echo twmp_get_svg_icon('contact-section-phone'); ?></span>
                            <a class="contact-us__meta-text contact-us__meta-link" href="<?php echo esc_url('tel:' . preg_replace('/[^0-9+]/', '', $hotline)); ?>">
                                <?php echo esc_html($hotline); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($social_items)) : ?>
                        <div class="contact-us__socials">
                            <?php foreach ($social_items as $item) : ?>
                                <a class="contact-us__social-link" href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr($item['label']); ?>">
                                    <?php echo twmp_get_svg_icon($item['icon']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                           
                <div class="contact-us__col contact-us__col--form">
                    <?php if( $data && $data['shortcode'] ): ?>
                    <div class="contact-us__form-wrap">
                        <?php echo do_shortcode(''.$data["shortcode"].''); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($data['enable_container']) : ?>
        </div>
    <?php endif; ?>
</section>