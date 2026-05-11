<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="buy-button-container">
	<div class="buy-button">
		<button type="button" class="add icon" aria-label="<?php esc_attr_e( 'Add to order', 'woocommerce-restaurant-ordering' ); ?>" aria-expanded="false" aria-controls="wro-product-modal">
			<div tabindex="0" class="quantity-minus">
				<img class="remove-from-cart" src="<?php echo $images_url . '/bin.svg'; ?>" alt="">
				<svg class="reduce-quantity" width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M11 4H5.5H0V7H11V4Z" fill="white"/>
				</svg>
			</div>
			<div tabindex="0" class="quantity-qty">0</div>
			<div tabindex="0" class="quantity-plus">
				<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g clip-path="url(#clip0_483_2192)"><path fill-rule="evenodd" clip-rule="evenodd" d="M4 4L4 0H7V4H11V7H7V11H4L4 7H0V4H4Z" fill="white"/></g><defs><clipPath id="clip0_483_2192"><rect width="11" height="11" fill="white"/></clipPath></defs>
				</svg>
			</div>
		</button>
	</div>
</div>
