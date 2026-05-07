<?php

/**
 * Cart totals
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-totals.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.6
 */

defined('ABSPATH') || exit;

$is_checkout_summary = function_exists('is_checkout') && is_checkout();

if ($is_checkout_summary) :
	$payment_state = function_exists('twmp_checkout_get_payment_order_context') ? twmp_checkout_get_payment_order_context() : array();
	if (function_exists('twmp_checkout_is_payment_step') && twmp_checkout_is_payment_step() && !empty($payment_state['order']) && $payment_state['order'] instanceof WC_Order) :
		$order = $payment_state['order'];
		?>
		<div class="twmp-checkout-summary__totals twmp-checkout-summary__totals--payment">
			<div class="twmp-checkout-summary__total-row">
				<span class="twmp-checkout-summary__total-label"><?php esc_html_e('Total', 'twmp-ath'); ?></span>
				<span class="twmp-checkout-summary__total-value"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
			</div>

			<div class="twmp-checkout-summary__status">
				<p class="twmp-checkout-summary__status-title"><?php echo esc_html(!empty($payment_state['status_label']) ? $payment_state['status_label'] : esc_html__('Waiting for confirmation', 'twmp-ath')); ?></p>
				<p class="twmp-checkout-summary__status-text"><?php echo esc_html(!empty($payment_state['status_text']) ? $payment_state['status_text'] : esc_html__('Upload your bill and wait for admin review.', 'twmp-ath')); ?></p>
				<p class="twmp-checkout-summary__status-meta"><?php echo esc_html(sprintf(__('Order #%s', 'twmp-ath'), $order->get_order_number())); ?></p>
			</div>
		</div>
		<?php
		return;
	endif;
	?>
	<div class="twmp-checkout-summary__totals">
		<div class="twmp-checkout-summary__total-row">
			<span class="twmp-checkout-summary__total-label"><?php esc_html_e('Total', 'twmp-ath'); ?></span>
			<span class="twmp-checkout-summary__total-value"><?php wc_cart_totals_order_total_html(); ?></span>
		</div>

	<div class="twmp-checkout-summary__actions">
		<button type="button" class="twmp-checkout-summary__button submit-thanh-toan" data-checkout-url="<?php echo esc_url(function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/')); ?>"><?php esc_html_e('Proceed to payment', 'twmp-ath'); ?></button>
	</div>
</div>
	<?php
	return;
endif;
?>
<div class="cart_totals <?php echo (WC()->customer->has_calculated_shipping()) ? 'calculated_shipping' : ''; ?>">

	<?php do_action('woocommerce_before_cart_totals'); ?>

	<h2><?php echo esc_html__('Cart total', 'twmp-ath'); ?></h2>

	<div class="order-min-amount">
		<span class="icons">
			<?php echo twmp_get_svg_icon('shipping'); ?>
		</span>
		<span class="text"><?php echo esc_html__( '- Order value does not include shipping costs.', 'twmp-ath' ) ?></span>
	</div>

	<table cellspacing="0" class="shop_table shop_table_responsive w-100">

		<tr class="cart-subtotal">
			<th><?php esc_html_e('Subtotal', 'twmp-ath'); ?></th>
			<td data-title="<?php esc_attr_e('Subtotal', 'twmp-ath'); ?>"><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
				<th><?php wc_cart_totals_coupon_label($coupon); ?></th>
				<td data-title="<?php echo esc_attr(wc_cart_totals_coupon_label($coupon, false)); ?>"><?php wc_cart_totals_coupon_html($coupon); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>

			<?php do_action('woocommerce_cart_totals_before_shipping'); ?>

			<?php wc_cart_totals_shipping_html(); ?>

			<?php do_action('woocommerce_cart_totals_after_shipping'); ?>

		<?php elseif (WC()->cart->needs_shipping() && 'yes' === get_option('woocommerce_enable_shipping_calc')) : ?>

			<tr class="shipping">
				<th><?php esc_html_e('Shipping', 'twmp-ath'); ?></th>
				<td data-title="<?php esc_attr_e('Shipping', 'twmp-ath'); ?>"><?php woocommerce_shipping_calculator(); ?></td>
			</tr>

		<?php endif; ?>

		<?php foreach (WC()->cart->get_fees() as $fee) : ?>
			<tr class="fee">
				<th><?php echo esc_html($fee->name); ?></th>
				<td data-title="<?php echo esc_attr($fee->name); ?>"><?php wc_cart_totals_fee_html($fee); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php
		if (wc_tax_enabled() && ! WC()->cart->display_prices_including_tax()) {
			$taxable_address = WC()->customer->get_taxable_address();
			$estimated_text  = '';

			if (WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping()) {
				/* translators: %s location. */
				$estimated_text = sprintf(' <small>' . esc_html__('(estimated for %s)', 'twmp-ath') . '</small>', WC()->countries->estimated_for_prefix($taxable_address[0]) . WC()->countries->countries[$taxable_address[0]]);
			}

			if ('itemized' === get_option('woocommerce_tax_total_display')) {
				foreach (WC()->cart->get_tax_totals() as $code => $tax) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
						<th><?php echo esc_html($tax->label) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
						<td data-title="<?php echo esc_attr($tax->label); ?>"><?php echo wp_kses_post($tax->formatted_amount); ?></td>
					</tr>
				<?php
				}
			} else {
				?>
				<tr class="tax-total">
					<th><?php echo esc_html(WC()->countries->tax_or_vat()) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
					<td data-title="<?php echo esc_attr(WC()->countries->tax_or_vat()); ?>"><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
		<?php
			}
		}
		?>

		<?php do_action('woocommerce_cart_totals_before_order_total'); ?>

		<tr class="order-total">
			<th><?php esc_html_e('Total', 'twmp-ath'); ?></th>
			<td data-title="<?php esc_attr_e('Total', 'twmp-ath'); ?>"><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action('woocommerce_cart_totals_after_order_total'); ?>

	</table>

	<div class="wc-proceed-to-checkout">
		<button class="submit-thanh-toan"><?php echo esc_html__( 'Order Confirmation', 'twmp-ath' ); ?></button>
	</div>

	<?php do_action('woocommerce_after_cart_totals'); ?>

</div>
