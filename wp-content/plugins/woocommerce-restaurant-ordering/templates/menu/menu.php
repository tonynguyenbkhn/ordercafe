<?php
defined( 'ABSPATH' ) || exit;
?>
<?php do_action( 'wc_restaurant_ordering_before_menu' ); ?>
<div id="<?php echo esc_attr( $menu_id ); ?>" class="wc-restaurant-menu">
	<?php do_action( 'wc_restaurant_ordering_before_restaurant_info' ); ?>
	<?php echo $restaurant_info; ?>
	<?php do_action( 'wc_restaurant_ordering_before_menu_navigation' ); ?>
	<?php echo $menu_navigation; ?>
	<?php do_action( 'wc_restaurant_ordering_before_menu_items' ); ?>
	<?php echo $menu_items; ?>
	<?php do_action( 'wc_restaurant_ordering_after_menu_items' ); ?>
</div>
<?php do_action( 'wc_restaurant_ordering_after_menu' ); ?>
