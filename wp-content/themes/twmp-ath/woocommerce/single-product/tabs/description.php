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

$heading = apply_filters('woocommerce_product_description_heading', __('Description', 'twmp-phonghoa'));

?>

<?php if ($heading) : ?>
	<h2><?php echo esc_html($heading); ?></h2>
<?php endif; ?>
<div class="tab-description-wrapper" data-block="show-less">
	<div class="js-content-toggle has-toggle">
		<div class="single__content">
			<?php the_content(); ?>
		</div>
		<?php get_template_part('templates/blocks/show-less', null, []); ?>
	</div>
</div>