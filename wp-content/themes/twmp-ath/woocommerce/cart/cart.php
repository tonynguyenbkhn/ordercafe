<?php

/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); ?>
<?php
$payment_state = function_exists('twmp_checkout_get_payment_order_context') ? twmp_checkout_get_payment_order_context() : array();
$is_payment_summary = function_exists('twmp_checkout_is_payment_step') && twmp_checkout_is_payment_step() && !empty($payment_state['order']) && $payment_state['order'] instanceof WC_Order;
$is_checkout_summary = function_exists('is_checkout') && is_checkout();
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

if ($is_checkout_summary) :
	$cart = function_exists('WC') ? WC()->cart : null;
	$selection = function_exists('twmp_checkout_get_ticket_selection_state') ? twmp_checkout_get_ticket_selection_state() : array();
	$selected_product_id = !empty($selection['product_id']) ? absint($selection['product_id']) : 0;
	$ticket_data = $selected_product_id && function_exists('twmp_checkout_get_ticket_product_data') ? twmp_checkout_get_ticket_product_data($selected_product_id) : array();
	?>
	<div class="twmp-checkout-summary">
		<div class="twmp-checkout-summary__card">
			<header class="twmp-checkout-summary__header">
				<h3 class="twmp-checkout-summary__title"><?php esc_html_e('Order summary', 'twmp-ath'); ?></h3>
			</header>

			<div class="twmp-checkout-summary__content">
				<?php if ($cart && !empty($cart->get_cart())) : ?>
					<?php foreach ($cart->get_cart() as $cart_item_key => $cart_item) : ?>
						<?php
						$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
						if (!$_product || !$_product->exists()) {
							continue;
						}

						$product_id = !empty($cart_item['product_id']) ? absint($cart_item['product_id']) : 0;
						$quantity = !empty($cart_item['quantity']) ? absint($cart_item['quantity']) : 1;
						$thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
						$product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
						$terms = get_the_terms($product_id, 'product_cat');
						$meta_lines = array();

						if ($selected_product_id && $selected_product_id === $product_id && !empty($ticket_data['performances']) && !empty($selection['performance_key']) && !empty($ticket_data['performances'][$selection['performance_key']])) {
							$meta_lines[] = $ticket_data['performances'][$selection['performance_key']]['display_short'];
						}

						if ($selected_product_id && $selected_product_id === $product_id && !empty($ticket_data['ticket_prices']) && !empty($selection['price_key']) && !empty($ticket_data['ticket_prices'][$selection['price_key']])) {
							$meta_lines[] = $ticket_data['ticket_prices'][$selection['price_key']]['label'];
						}

						if (!empty($terms) && !is_wp_error($terms)) {
							foreach ($terms as $term) {
								$meta_lines[] = $term->name;
							}
						}
						?>
						<article class="twmp-checkout-summary__item">
							<div class="twmp-checkout-summary__media">
								<?php echo wp_kses_post($thumbnail); ?>
							</div>
							<div class="twmp-checkout-summary__body">
								<div class="twmp-checkout-summary__meta">
									<?php foreach (array_slice(array_unique($meta_lines), 0, 3) as $meta_line) : ?>
										<span class="twmp-checkout-summary__badge"><?php echo esc_html($meta_line); ?></span>
									<?php endforeach; ?>
								</div>
								<h4 class="twmp-checkout-summary__name"><?php echo wp_kses_post($product_name); ?></h4>
								<?php if (!empty($meta_lines)) : ?>
									<p class="twmp-checkout-summary__details"><?php echo esc_html(implode(' · ', array_slice(array_unique($meta_lines), 0, 2))); ?></p>
								<?php endif; ?>
							</div>
							<div class="twmp-checkout-summary__aside">
								<div class="twmp-checkout-summary__price"><?php echo wp_kses_post(wc_price($_product->get_price() * $quantity)); ?></div>
								<div class="twmp-checkout-summary__qty"><?php echo esc_html('x' . $quantity); ?></div>
							</div>
						</article>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="twmp-checkout-summary__empty"><?php esc_html_e('Your cart is empty.', 'twmp-ath'); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<?php wc_get_template('cart/cart-totals.php'); ?>
	</div>
	<?php return; ?>
<?php endif; ?>

$total_cart_items = count(WC()->cart->get_cart());
?>
<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
	<?php do_action('woocommerce_before_cart_table'); ?>
	<h3 class="title-cat"><?php echo sprintf(__('There are %d products in your cart', 'twmp-ath'), $total_cart_items); ?></h3>
	<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
		<thead class="d-none">
			<tr>
				<th class="product-remove"><span class="screen-reader-text"><?php esc_html_e('Remove item', 'twmp-ath'); ?></span></th>
				<th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e('Thumbnail image', 'twmp-ath'); ?></span></th>
				<th class="product-name"><?php esc_html_e('Product', 'twmp-ath'); ?></th>
				<th class="product-price"><?php esc_html_e('Price', 'twmp-ath'); ?></th>
				<th class="product-quantity"><?php esc_html_e('Quantity', 'twmp-ath'); ?></th>
				<th class="product-subtotal"><?php esc_html_e('Subtotal', 'twmp-ath'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php do_action('woocommerce_before_cart_contents'); ?>

			<?php
			foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
				$_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
				$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
				/**
				 * Filter the product name.
				 *
				 * @since 2.1.0
				 * @param string $product_name Name of the product in the cart.
				 * @param array $cart_item The product in the cart.
				 * @param string $cart_item_key Key for the product in the cart.
				 */
				$product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);

				if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
					$product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
			?>
					<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
						<td class="product-thumbnail">
							<?php
							/**
							 * Filter the product thumbnail displayed in the WooCommerce cart.
							 *
							 * This filter allows developers to customize the HTML output of the product
							 * thumbnail. It passes the product image along with cart item data
							 * for potential modifications before being displayed in the cart.
							 *
							 * @param string $thumbnail     The HTML for the product image.
							 * @param array  $cart_item     The cart item data.
							 * @param string $cart_item_key Unique key for the cart item.
							 *
							 * @since 2.1.0
							 */
							$thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);

							if (! $product_permalink) {
								echo wp_kses_post( $thumbnail ); // PHPCS: XSS ok.
							} else {
								printf('<a href="%s">%s</a>', esc_url($product_permalink), wp_kses_post( $thumbnail ) ); // PHPCS: XSS ok.
							}
							?>
						</td>
						<td class="product-right">
							<div class="content-product">
								<div class="product-name" data-title="<?php esc_attr_e('Product', 'twmp-ath'); ?>">
									<?php
									if (! $product_permalink) {
										echo wp_kses_post($product_name . '&nbsp;');
									} else {
										/**
										 * This filter is documented above.
										 *
										 * @since 2.1.0
										 */
										echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
									}

									do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);

									// Meta data.
									echo wc_get_formatted_cart_item_data($cart_item); // PHPCS: XSS ok.

									// Backorder notification.
									if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
										echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'twmp-ath') . '</p>', $product_id));
									}
									?>
								</div>
								<div class="product-subtotal" data-title="<?php esc_attr_e('Subtotal', 'twmp-ath'); ?>">
									<?php
									$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
									$quantity = $cart_item['quantity'];

									if ($_product->is_on_sale()) {
										$regular_price_html = wc_price($_product->get_regular_price() * $quantity);
										$sale_price_html    = wc_price($_product->get_sale_price() * $quantity);
										echo '<del>' . wp_kses_post( $regular_price_html ) . '</del>';
										echo '<ins style="text-decoration: none;">' . wp_kses_post( $sale_price_html ) . '</ins>';
									} else {
										$price_html = wc_price($_product->get_price() * $quantity);
										echo wp_kses_post( $price_html );
									}
									?>
								</div>
								<div class="product-remove">
									<?php
									echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										'woocommerce_cart_item_remove_link',
										sprintf(
											'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">'.esc_html__( 'Delete', 'twmp-ath' ).'</a>',
											esc_url(wc_get_cart_remove_url($cart_item_key)),
											/* translators: %s is the product name */
											esc_attr(sprintf(__('Remove %s from cart', 'twmp-ath'), wp_strip_all_tags($product_name))),
											esc_attr($product_id),
											esc_attr($_product->get_sku())
										),
										$cart_item_key
									);
									?>
								</div>
								<div class="footer-b">
									<div class="product-quantity">
										<?php
										if ($_product->is_sold_individually()) {
											$min_quantity = 1;
											$max_quantity = 1;
										} else {
											$min_quantity = 0;
											$max_quantity = $_product->get_max_purchase_quantity();
										}

										$product_quantity = woocommerce_quantity_input(
											array(
												'input_name'   => "cart[{$cart_item_key}][qty]",
												'input_value'  => $cart_item['quantity'],
												'max_value'    => $max_quantity,
												'min_value'    => $min_quantity,
												'product_name' => $product_name,
											),
											$_product,
											false
										);

										echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item); // PHPCS: XSS ok.
										?>
									</div>
								</div>
							</div>
						</td>
					</tr>
			<?php
				}
			}
			?>

			<?php do_action('woocommerce_cart_contents'); ?>

			<tr>
				<td colspan="6" class="actions">

					<?php if (wc_coupons_enabled()) { ?>
						<div class="coupon">
							<label for="coupon_code" class="screen-reader-text"><?php esc_html_e('Coupon:', 'twmp-ath'); ?></label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e('Coupon code', 'twmp-ath'); ?>" /> <button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'twmp-ath'); ?>"><?php esc_html_e('Apply coupon', 'twmp-ath'); ?></button>
							<?php do_action('woocommerce_cart_coupon'); ?>
						</div>
					<?php } ?>

					<button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="update_cart" value="<?php esc_attr_e('Update cart', 'twmp-ath'); ?>"><?php esc_html_e('Update cart', 'twmp-ath'); ?></button>

					<?php do_action('woocommerce_cart_actions'); ?>

					<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
				</td>
			</tr>

			<?php do_action('woocommerce_after_cart_contents'); ?>
		</tbody>
	</table>
	<?php do_action('woocommerce_after_cart_table'); ?>
</form>

<?php do_action('woocommerce_before_cart_collaterals'); ?>
<?php wc_get_template(
	'checkout/form-coupon.php',
	array(
		'checkout' => WC()->checkout(),
	)
); ?>
<div class="cart-collaterals">
	<?php wc_get_template('cart/cart-totals.php'); ?>
</div>

<?php do_action('woocommerce_after_cart'); ?>
