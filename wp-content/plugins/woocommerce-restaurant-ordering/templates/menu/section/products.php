<?php
defined( 'ABSPATH' ) || exit;
?>
<?php do_action( 'wc_restaurant_ordering_before_menu_section_products' ); ?>
<div class="wc-restaurant-menu-products <?php echo esc_attr( $products_class ); ?>">
	<?php echo $products; ?>
</div>
<?php do_action( 'wc_restaurant_ordering_after_menu_section_products' ); ?>