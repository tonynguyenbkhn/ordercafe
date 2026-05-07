<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image component
 * Expected args (via get_template_part third param):
 * - 'id' (int) attachment ID
 * - 'size' (string) image size, default 'full'
 * - 'class' (string) additional classes
 * - 'alt' (string) alt text override
 * - 'loading' (string) 'lazy'|'eager'
 * - 'sizes' (string) sizes attribute
 */

$data = wp_parse_args($args, [
    'id' => 0,
    'size' => 'full',
    'class' => '',
    'alt' => '',
    'loading' => 'lazy',
    'sizes' => '',
]);

if (empty($data['id'])) {
    return;
}

$id = (int) $data['id'];
$class = esc_attr($data['class']);
$loading = in_array($data['loading'], ['lazy','eager'], true) ? $data['loading'] : 'lazy';
$sizes = $data['sizes'] ? $data['sizes'] : '';

$alt = $data['alt'];
if (empty($alt)) {
    $meta_alt = get_post_meta($id, '_wp_attachment_image_alt', true);
    $alt = $meta_alt ? $meta_alt : '';
}

echo wp_get_attachment_image($id, $data['size'], false, [
    'class' => $class,
    'alt' => $alt,
    'loading' => $loading,
    'sizes' => $sizes,
]);
