<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package taiwebmienphi
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header d-none">
		<?php
		if ( 'post' === get_post_type() ) :
			get_template_part('templates/blocks/post-meta', null, [
				'date' => true,
				'author' => true,
				'categories' => true,
				'class' => 'post-card__post-meta'
			]);
		endif; ?>
	</header><!-- .entry-header -->

	<?php
		if (has_post_thumbnail(get_the_ID()) && get_the_post_thumbnail_url(get_the_ID())) {
			get_template_part('templates/core-blocks/image', null, [
				'image_id' => get_post_thumbnail_id(get_the_ID()),
				'image_size' => 'full',
				'lazyload' => false,
				'class' => 'pe-none image--default single-theme__image-feature',
			]);
		}
	?>

	<div class="entry-content">
		<?php
		the_content(
			sprintf(
				wp_kses(
					/* translators: %s: Name of current post. Only visible to screen readers */
					__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'twmp-ath' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				wp_kses_post( get_the_title() )
			)
		);

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'twmp-ath' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
		<?php // taiwebmienphi_entry_footer(); ?>
	</footer><!-- .entry-footer -->
</article><!-- #post-<?php the_ID(); ?> -->
