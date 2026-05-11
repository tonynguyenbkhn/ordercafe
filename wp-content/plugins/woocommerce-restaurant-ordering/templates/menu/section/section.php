<?php
defined( 'ABSPATH' ) || exit;
?>
<?php do_action( 'wc_restaurant_ordering_before_menu_section' ); ?>
    <div id="<?php echo esc_attr( $section_id ); ?>" class="wc-restaurant-menu-section">
        <?php echo $title; ?>
        <?php echo $products; ?>
    </div>
<?php do_action( 'wc_restaurant_ordering_after_menu_section' ); ?>