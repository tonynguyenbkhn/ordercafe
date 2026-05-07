<?php

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('WooCommerce')):

$data = wp_parse_args($args, [
	'link' => class_exists('WooCommerce') && function_exists('wc_get_cart_url') ? wc_get_cart_url() : null,
]);

$_class = 'cart-icon';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_col_class = 'post-grid__col';
$_col_class .= !empty($data['col_class']) ? esc_attr(' ' . $data['col_class']) : '';
?>

<a class="<?php echo esc_attr( $_class ); ?>" href="<?php echo esc_url( $data['link'] ); ?>" target="_self">
	<span class="cart-icon__inner">
		<?php echo twmp_get_svg_icon('cart') ?>
		<span><?php echo esc_html__('Cart', 'twmp-ath') ?></span>
	</span>
	<?php
	if (is_object(WC()->cart) && !empty(WC()->cart)) : ?>
		<span class="cart-icon__count">
			<?php
			$count = WC()->cart->get_cart_contents_count();
			printf(_n('%d', '%d', $count, 'twmp-ath'), $count);
			?>
		</span>
	<?php endif; ?>
	<span class="screen-reader-text"><?php echo esc_html__('Cart', 'twmp-ath') ?></span>
</a>

<?php
endif;