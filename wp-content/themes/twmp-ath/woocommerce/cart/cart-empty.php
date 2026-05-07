<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined('ABSPATH') || exit;

$payment_state = function_exists('twmp_checkout_get_payment_order_context') ? twmp_checkout_get_payment_order_context() : array();
$is_payment_summary = function_exists('twmp_checkout_is_payment_step') && twmp_checkout_is_payment_step() && !empty($payment_state['order']) && $payment_state['order'] instanceof WC_Order;

if ($is_payment_summary) :
	$order = $payment_state['order'];
	$order_items = function_exists('twmp_checkout_build_order_summary_data') ? twmp_checkout_build_order_summary_data($order) : array();
	?>
	<div class="twmp-checkout-summary twmp-checkout-summary--payment">
		<div class="twmp-checkout-summary__card">
			<header class="twmp-checkout-summary__header">
				<h3 class="twmp-checkout-summary__title"><?php esc_html_e('Order summary', 'twmp-ath'); ?></h3>
			</header>

			<div class="twmp-checkout-summary__content">
				<?php if (!empty($order_items)) : ?>
					<?php foreach ($order_items as $item) : ?>
						<article class="twmp-checkout-summary__item">
							<div class="twmp-checkout-summary__media">
								<?php echo !empty($item['image']) ? wp_kses_post($item['image']) : ''; ?>
							</div>
							<div class="twmp-checkout-summary__body">
								<div class="twmp-checkout-summary__meta">
									<?php foreach (!empty($item['badges']) ? array_slice($item['badges'], 0, 3) : array() as $badge) : ?>
										<span class="twmp-checkout-summary__badge"><?php echo esc_html($badge); ?></span>
									<?php endforeach; ?>
								</div>
								<h4 class="twmp-checkout-summary__name"><?php echo esc_html($item['name']); ?></h4>
							</div>
							<div class="twmp-checkout-summary__aside">
								<div class="twmp-checkout-summary__qty"><?php echo esc_html('x' . absint($item['quantity'])); ?></div>
							</div>
						</article>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="twmp-checkout-summary__empty"><?php esc_html_e('Your order is ready for payment proof review.', 'twmp-ath'); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php wc_get_template('cart/cart-totals.php'); ?>
	<?php
	return;
endif;

do_action('woocommerce_cart_is_empty');

if (wc_get_page_id('shop') > 0) : ?>
	<p class="return-to-shop">
		<a class="button wc-backward<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>">
			<?php
				/**
				 * Filter "Return To Shop" text.
				 *
				 * @since 4.6.0
				 * @param string $default_text Default text.
				 */
				echo esc_html(apply_filters('woocommerce_return_to_shop_text', __('Return to shop', 'woocommerce')));
			?>
		</a>
	</p>
<?php endif; ?>
