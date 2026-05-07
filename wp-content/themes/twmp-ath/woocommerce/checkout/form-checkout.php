<?php

/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if (! defined('ABSPATH')) {
	exit;
}

do_action('woocommerce_before_checkout_form', $checkout);

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (! $checkout->is_registration_enabled() && $checkout->is_registration_required()) {
	echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'twmp-ath')));
	return;
}

$is_payment_step = function_exists('twmp_checkout_is_payment_step_2') && twmp_checkout_is_payment_step_2();

if ($is_payment_step) {
	if (function_exists('twmp_checkout_render_payment_step_section')) {
		twmp_checkout_render_payment_step_section();
	}

	do_action('woocommerce_after_checkout_form', $checkout);
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'twmp-ath'); ?>">

	<?php if ($checkout->get_checkout_fields()) : ?>

		<div class="twmp-checkout-stack">
			<section class="twmp-checkout-card twmp-checkout-card--booking">
				<header class="twmp-checkout-card__header">
					<span class="twmp-checkout-card__step">1</span>
					<h3 class="twmp-checkout-card__title"><?php esc_html_e('Ticket booking information', 'twmp-ath'); ?></h3>
				</header>

				<div class="twmp-checkout-card__content">
					<?php do_action('woocommerce_checkout_billing'); ?>
				</div>
			</section>

			<?php do_action('woocommerce_checkout_after_customer_details'); ?>
		</div>

	<?php endif; ?>

	<?php do_action('woocommerce_checkout_before_order_review_heading'); ?>

	<div class="order_review_wrapper twmp-checkout-payment">
		<h3 id="order_review_heading"><?php esc_html_e('Payment method', 'twmp-ath'); ?></h3>

		<?php do_action('woocommerce_checkout_before_order_review'); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php do_action('woocommerce_checkout_order_review'); ?>
		</div>
	</div>

	<?php do_action('woocommerce_checkout_after_order_review'); ?>

</form>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
