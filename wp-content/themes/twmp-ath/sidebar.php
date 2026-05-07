<?php
/**
 * The sidebar containing the main widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package taiwebmienphi
 */

if (!defined('ABSPATH')) {
    exit;
}

if ( ! is_active_sidebar( 'sidebar-category' ) ) {
	return;
}
?>

<aside id="secondary" class="widget-area">
	<?php dynamic_sidebar( 'sidebar-category' ); ?>
</aside><!-- #secondary -->
