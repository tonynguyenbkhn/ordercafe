<?php
defined( 'ABSPATH' ) || exit;
?>
<?php do_action( 'wc_restaurant_ordering_before_menu_product' ); ?>
<div class="wc-restaurant-menu-product" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-order-type="<?php echo esc_attr( $order_type ); ?>" data-quantity="<?php echo esc_attr( $quantity ); ?>" <?php echo esc_attr( $item_attrs ); ?> >
	<div class="wc-restaurant-menu-product-inner">
		<?php echo $image; ?>
		<div class="details">
			<?php if ( $columns > 1 ) : ?>
				<div class="header">
					<?php echo $name; ?>
					<?php echo $price; ?>
				</div>
				<?php echo $description; ?>
				<?php echo $buy_button; ?>
			<?php else : ?>
				<div class="header">
					<?php echo $name; ?>
					<?php echo $price; ?>
					<?php echo $buy_button; ?>
				</div>
				<?php echo $description; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php do_action( 'wc_restaurant_ordering_after_menu_product' ); ?>
