<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args($args, [
    'class' => '',
    'image_id' => '',
    'image_size' => '',
    'image_class' => '',
    'lazyload' => true,
    'alt' => '',
    'sizes' => '',
    'srcset' => '',
    'fancybox' => false,
    'fancybox_type' => 'gallery',
]);

$_class = 'image';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$alt_text = get_post_meta($data['image_id'], '_wp_attachment_image_alt', true);
$attr['alt'] = !empty($data['alt']) ? $data['alt'] : ( $alt_text ? $alt_text : get_bloginfo('name') );

if ($data['sizes']) {
    $attr['sizes'] = $data['sizes'];
}
if ($data['srcset']) {
    $attr['srcset'] = $data['srcset'];
}
if (!empty($data['image_id'])) :
    $_image_class = 'image__img';
    $_image_class .= !empty($data['image_class']) ? esc_attr(' ' . $data['image_class']) : '';
    $attr['class'] = $_image_class;
    if (! $data['lazyload']) {
        $_image_class .= ' no-lazy';
    }
?>
    <?php if ($data['fancybox']) : ?><a data-fancybox="<?php echo esc_attr($data['fancybox_type']); ?>" data-src="<?php echo esc_url(wp_get_attachment_image_url($data['image_id'], $data['image_size'], false)); ?>"><?php endif; ?>
    <figure class="<?php echo esc_attr($_class); ?>">
        <?php echo wp_get_attachment_image($data['image_id'], $data['image_size'], false, $attr); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </figure>
    <?php if ($data['fancybox']) : ?></a><?php endif; ?>
<?php endif;
