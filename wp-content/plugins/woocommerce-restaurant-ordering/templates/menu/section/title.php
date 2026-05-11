<?php
defined( 'ABSPATH' ) || exit;
?>
<?php do_action( 'wc_restaurant_ordering_before_menu_section_title' ); ?>
<?php if ( $title ) : ?>
	<h2 class="wc-restaurant-menu-section-title"><?php echo esc_html( $title ); ?></h2>
<?php endif; ?>
<?php do_action( 'wc_restaurant_ordering_before_menu_section_description' ); ?>
<?php if ( $description ) : ?>
	<div class="wc-restaurant-menu-section-description"><?php echo $description; ?></div>
<?php endif; ?>
<?php do_action( 'wc_restaurant_ordering_after_menu_section_title' ); ?>
