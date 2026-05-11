<?php
defined( 'ABSPATH' ) || exit;

$timepicker_fmt = '<input type="text" name="%1$s[%2$s][%3$s][%4$s]" class="opening-hours-time timepicker" value="%5$s" />';
?>
<td class="opening-hours-day-period" data-period="<?php echo esc_attr( $period ); ?>">
	<?php printf( $timepicker_fmt, esc_attr( $id ), esc_attr( $day ), esc_attr( $period ), 'from', $from_value ); ?>
	<span class="to-label"><?php _e( 'to', 'woocommerce-restaurant-ordering' ); ?></span>
	<?php printf( $timepicker_fmt, esc_attr( $id ), esc_attr( $day ), esc_attr( $period ), 'to', $to_value ); ?>
</td>
