<?php
defined( 'ABSPATH' ) || exit;
?>
<# if ( data.styles ) { #>
	{{{ data.styles }}}
<# } #>
<div class="wc-restaurant-product wc-backbone-modal-content">
	<div class="product-content">
		<# if ( data.image ) { #>
		<header class="image" style="background-image:url({{ data.image }})"></header>
		<# } #>
		<section class="details">
			<?php do_action( 'wc_restaurant_ordering_before_modal_details' ); ?>
			<h2 class="name">{{ data.product_name }}</h2>
			<# if ( ! data.purchasable ) { #>
				<div class="price">{{{ data.display_price }}}</div>
			<# } #>
			<# if ( data.description ) { #>
				<div class="description">{{{ data.description }}}</div>
			<# } #>
			<# if ( data.stock && data.in_stock ) { #>
				{{{ data.stock }}}
			<# } #>
			<# if ( data.purchasable ) { #>
				<# if ( data.options ) { #>
					<div class="options">{{{ data.options }}}</div>
				<# } #>
			<# } #>
			<?php do_action( 'wc_restaurant_ordering_after_modal_details' ); ?>
		</section>	
	</div>
	<footer class="order <# if ( data.purchasable ) { #>purchasable<# } else { #>not-purchasable<# } #>">
		<?php do_action( 'wc_restaurant_ordering_before_modal_footer' ); ?>

		<# if ( !data.accepting_orders ) { #>
			<p class="availability-notice restaurant-closed">{{ data.closed_notice }}</p>
		<# } else if ( ! data.in_stock ) { #>
			<p class="availability-notice out-of-stock"><?php esc_html_e( 'Item out of stock', 'woocommerce-restaurant-ordering' ); ?></p>
		<# } else if ( ! data.purchasable ) { #>
			<p class="availability-notice not-available"><?php esc_html_e( 'Item not available', 'woocommerce-restaurant-ordering' ); ?></p>
		<# } else { #>
			<span class="quantity">
				<button type="button" class="remove" aria-label="<?php esc_attr_e( 'Decrease quantity', 'woocommerce-restaurant-ordering' ); ?>">-</button>
				<input type="number" name="quantity" class="qty" required aria-label="<?php esc_attr_e( 'Enter item quantity', 'woocommerce-restaurant-ordering' ); ?>"
					   value="{{ data.quantity.value }}"
					   min="{{ data.quantity.min }}"
					   max="{{ data.quantity.max }}"
					   step="{{ data.quantity.step }}" />
				<button type="button" class="add" aria-label="<?php esc_attr_e( 'Increase quantity', 'woocommerce-restaurant-ordering' ); ?>">+</button>
			</span>
			<?php echo $buy_button; ?>
		<# } #>
		<?php do_action( 'wc_restaurant_ordering_after_modal_footer' ); ?>
	</footer>
</div>
