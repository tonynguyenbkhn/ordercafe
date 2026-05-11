<?php

use Barn2\Plugin\WC_Restaurant_Ordering\Info\Opening_Hours;
use Barn2\Plugin\WC_Restaurant_Ordering\Info\Opening_Hours_Formatter;

defined( 'ABSPATH' ) || exit;

$opening_hours          = new Opening_Hours( $current_value );
$empty_period           = array_fill_keys( [ 1 ], [] );
$has_additional_periods = $opening_hours->has_additional_opening_periods();

if ( $has_additional_periods ) {
	$field['class'] .= ' additional-periods';
	$empty_period   = array_fill_keys( [ 1, 2 ], [] );
}

?>
<tr>
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?><?php echo $description['tooltip_html']; ?></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?> setting-opening-hours">
		<?php echo $description['description']; ?>
		<table id="<?php echo esc_attr( $field['id'] ); ?>" class="opening-hours-table <?php echo esc_attr( trim( $field['class'] ) ); ?>">
			<tbody>
			<?php foreach ( Opening_Hours_Formatter::format( $opening_hours ) as $day => $formatted_periods ) : ?>
				<tr class="opening-hours-day" data-day="<?php echo esc_attr( $day ); ?>">
					<td class="opening-hours-day-label"><?php echo esc_html( $formatted_periods['label'] ); ?></td>
					<?php
					// If the day has less than the current max number of opening periods (1 or 2), add an empty period
					// to it as we want to display time inputs for each potential opening period (we use the + operator
					// so that days which already have the maximum or not affected).
					$opening_periods = $formatted_periods['periods'] + $empty_period;

					foreach ( $opening_periods as $period => $opening_period ) {
						$from = ! empty( $opening_period['from'] ) ? $opening_period['from'] : '';
						$to   = ! empty( $opening_period['to'] ) ? $opening_period['to'] : '';

						$template_loader->load_template(
							'admin/opening-hours/opening-period.php',
							[
								'id'         => $field['id'],
								'day'        => $day,
								'period'     => $period,
								'from_value' => $from,
								'to_value'   => $to
							]
						);
					}
					?>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr>
				<td class="change-periods-action" colspan="<?php echo esc_attr( $has_additional_periods ? 3 : 2 ); ?>">
					<?php
					$more_style = $has_additional_periods ? 'display:none;' : '';
					$less_style = $has_additional_periods ? '' : 'display:none;';
					?>
					<a class="change-periods add-more" data-action="add" href="#" style="<?php echo esc_attr( $more_style ); ?>"><?php _e( 'Add more hours', 'woocommerce-restaurant-ordering' ); ?></a>
					<a class="change-periods use-less" data-action="remove" href="#"
					   style="<?php echo esc_attr( $less_style ); ?>"><?php _e( 'Use fewer hours', 'woocommerce-restaurant-ordering' ); ?></a>
				</td>
			</tr>
			</tfoot>
		</table>
	</td>
</tr>
