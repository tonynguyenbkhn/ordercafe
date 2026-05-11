<?php
defined( 'ABSPATH' ) || exit;

use Barn2\Plugin\WC_Restaurant_Ordering\Info\Opening_Hours;
use Barn2\Plugin\WC_Restaurant_Ordering\Info\Opening_Hours_Formatter;

?>
<div class="wc-restaurant-info-modal-content wc-backbone-modal-content">
	<?php if ( $restaurant_name || $restaurant_address ) : ?>
	<div class="wc-restaurant-info-modal-header">
		<?php if ( $restaurant_name ) : ?>
			<h2 class="wc-restaurant-info-name"><?php echo esc_html( $restaurant_name ); ?></h2>
		<?php endif; ?>
		<?php if ( $restaurant_address ) : ?>
			<p class="wc-restaurant-info-address wc-restaurant-info-item">
				<img class="icon address-icon" src="<?php echo esc_attr( $images_url . '/location-pin.svg' ); ?>" alt="" role="presentation" />
				<span class="address-text"><?php echo esc_html( $restaurant_address ); ?></span>
				<img class="icon copy-icon clickable" id="wro-info-copy-address" src="<?php echo esc_attr( $images_url . '/copy.svg' ); ?>" alt="" role="presentation" />
			</p>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<div class="wc-restaurant-info-details">
		<?php if ( $opening_hours instanceof Opening_Hours && $opening_hours->is_valid() ) : ?>
			<div class="wc-restaurant-opening-times <?php echo esc_attr( $is_restaurant_open ? 'restaurant-open' : 'restaurant-closed' ); ?>">
				<?php foreach ( Opening_Hours_Formatter::format( $opening_hours ) as $day => $formatted_periods ) : ?>
					<?php
					$day_classes = [ 'opening-periods-day' ];

					if ( ! empty( $formatted_periods['periods'] ) ) {
						$day_classes[] = 'has-open-periods';
					}

					if ( $current_day === $day ) {
						$day_classes[] = 'current-day';
					}
					?>
					<div class="<?php echo esc_attr( implode( ' ', $day_classes ) ); ?>">
						<span class="day-label"><?php echo esc_html( $formatted_periods['label'] ); ?></span>
						<?php if ( empty( $formatted_periods['periods'] ) ) : ?>
							<span class="period-detail"><?php esc_html_e( 'Closed', 'woocommerce-restaurant-ordering' ); ?></span>
						<?php else : ?>
							<?php foreach ( $formatted_periods['periods'] as $period => $times ) : ?>
								<span class="period-detail <?php echo esc_attr( sprintf( 'open-period-%s', $period ) ); ?>">
								<span class="open-time"><?php echo esc_html( $times['from'] ); ?></span> <?php echo esc_html_x( 'to', 'displayed between the open and close times', 'woocommerce-restaurant-ordering' ); ?>
								<span class="close-time"><?php echo esc_html( $times['to'] ); ?></span>
							</span>
							<?php endforeach; // opening period ?>
						<?php endif; ?>
					</div>
				<?php endforeach; // day ?>
			</div>
		<?php endif; ?>
	</div>
</div>

