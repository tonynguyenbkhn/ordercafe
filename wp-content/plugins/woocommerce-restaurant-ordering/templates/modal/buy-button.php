<?php
defined( 'ABSPATH' ) || exit;
?>
<button type="submit" class="buy" id="add-product"><# if ( data.display_price ) { #><?php esc_html_e( 'Add for', 'woocommerce-restaurant-ordering' ); ?> <span class="total" id="product-total-{{ data.product_id }}" data-item-price="{{ data.price }}">{{{ data.display_price }}}</span><# } else { #><?php esc_html_e( 'Add to order', 'woocommerce-restaurant-ordering' ); ?><# } #></button>