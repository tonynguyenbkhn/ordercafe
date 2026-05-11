<?php
defined( 'ABSPATH' ) || exit;
// $image = the URL of the product image.
?>
<?php if ( $image ) : ?><div class="image" style="background-image:url('<?php echo esc_attr( $image ); ?>');"></div><?php endif; ?>