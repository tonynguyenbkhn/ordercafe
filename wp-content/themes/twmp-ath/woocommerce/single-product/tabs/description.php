<?php

/**
 * Description tab
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/description.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

global $post;

$heading = apply_filters('woocommerce_product_description_heading', __('Description', 'twmp-ath'));

?>

<?php if ($heading) : ?>
	<h2><?php echo esc_html($heading); ?></h2>
<?php endif; ?>
<div class="tab-description-wrapper">
	<div class="js-content-toggle has-toggle">
		<div class="single__content">
			<div class="d-flex items-center gap-8">
				<?php echo twmp_get_svg_icon('cpny-name'); ?>
				<span style="color: black;" class="typo-display-xs-bold"><?php echo esc_html__('Conpany Name', 'twmp-ath'); ?></span>
			</div>
			<?php the_content(); ?>
			<?php get_template_part('templates/woocommerces/single-product/artists', null, []); ?>
		</div>
	</div>
</div>