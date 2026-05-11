<?php
defined( 'ABSPATH' ) || exit;
?>
<?php if ( $categories ) : ?>
	<nav class="wc-restaurant-navigation">
		<div class="wc-restaurant-navigation-inner wc-restaurant-navigation-holder">
			<ul class="wc-restaurant-navigation-items">
				<?php foreach ( $categories as $category ) : ?>
					<?php $category_name = sanitize_term_field( 'name', $category->name, $category->term_id, 'product_cat', 'display' ); ?>
					<li>
						<a href="<?php echo esc_attr( sprintf( '#%s%s', $anchor_prefix, $category->slug ) ); ?>"><?php echo esc_html( $category_name ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="wc-restaurant-navigation-more hidden">
				<button class="more-button" tabindex="0"><?php esc_html_e( 'More', 'woocommerce-restaurant-ordering' ); ?></button>
				<ul class="more-dropdown"></ul>
			</div>
		</div>
	</nav>
<?php endif; ?>