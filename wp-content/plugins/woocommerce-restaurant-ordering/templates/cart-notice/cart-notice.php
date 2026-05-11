<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="wc-restaurant-ordering-notice <# if ( data.success ) { #>success-notice<# } else { #>error-notice<# } #>" style="display:none;">
	<# if ( data.success ) { #>
		<p class="notice-text">
			<# if ( data.quantity_removed ) { #>
				<?php /* translators: %s: The names of the products added to the cart. */ ?>
				<?php printf( esc_html__( '%s has been removed from your cart.', 'woocommerce-restaurant-ordering' ), '{{{ data.product_name }}}' ); ?>
			<# } else if ( data.quantity_added > 1 ) { #>
				<?php /* translators: %s: The names of the products added to the cart. */ ?>
				<?php printf( esc_html__( '%s have been added to your cart.', 'woocommerce-restaurant-ordering' ), '{{{ data.product_name }}}' ); ?>
			<# } else { #>
				<?php /* translators: %s: The name of the product added to the cart. */ ?>
				<?php printf( esc_html__( '%s has been added to your cart.', 'woocommerce-restaurant-ordering' ), '{{{ data.product_name }}}' ); ?>
			<# } #>
			<?php printf( '<a href="%s" class="view-cart"><span class="view-cart-text">%s</span> &rarr;</a>', esc_url( wc_get_cart_url() ), esc_html__( 'View cart', 'woocommerce-restaurant-ordering' ) ); ?>
		</p>
	<# } else if ( data.error_message ) { #>
		{{{ data.error_message }}}
	<# } else { #>
		<p class="notice-text"><?php esc_html_e( 'Sorry, there was a problem ordering this item.', 'woocommerce-restaurant-ordering' ); ?></p>
	<# } #>
</div>
