<?php
defined( 'ABSPATH' ) || exit;

$availability_class = $is_restaurant_open ? 'restaurant-open' : 'restaurant-closed';
$more_link          = $show_more_link ?
	sprintf( '<span class="wc-restaurant-info-more">&bull; <button type="button" aria-controls="wro-info-modal" aria-expanded="false" aria-label="View open hours schedule">%s</button>', esc_html__( 'More', 'woocommerce-restaurant-ordering' ) ) : '';
?>
<div class="wc-restaurant-info-menu wc-restaurant-info <?php echo esc_attr( $availability_class ); ?>">
	<?php if ( $restaurant_address ) : ?>
		<p class="wc-restaurant-info-address wc-restaurant-info-item">
			<img class="icon address-icon" src="<?php echo esc_attr( $images_url . '/location-pin.svg' ); ?>" alt="" role="presentation" />
			<span class="address-text"><?php echo esc_html( $restaurant_address ); ?></span>
			<?php // phpcs:ignore:WordPress.Security.EscapeOutput ?>
			<?php echo $more_link; ?>
		</p>
	<?php endif; ?>
	<?php if ( $show_availability_notice && $availability_notice ) : ?>
		<p class="wc-restaurant-info-availability wc-restaurant-info-item <?php echo esc_attr( $availability_class ); ?>">
			<img class="icon availability-icon" src="<?php echo esc_attr( $images_url . '/clock.svg' ); ?>" alt="" role="presentation" />
			<span class="availability-text"><?php echo esc_html( $availability_notice ); ?></span>
			<?php
			// Show the more link next to the availability if there's no restaurant address.
			if ( ! $restaurant_address ) {
				echo $more_link; // phpcs:ignore:WordPress.Security.EscapeOutput
			}
			?>
		</p>
	<?php endif; ?>
	<?php if ( $show_delivery_notice && $delivery_notice ) : ?>
		<p class="wc-restaurant-info-delivery wc-restaurant-info-item">
			<img class="icon delivery-icon" src="<?php echo esc_attr( $images_url . '/delivery.svg' ); ?>" alt="" role="presentation" />
			<span class="delivery-text"><?php echo esc_html( $delivery_notice ); ?></span>
		</p>
	<?php endif; ?>
</div>
