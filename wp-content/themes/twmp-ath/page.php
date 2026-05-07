<?php

/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package taiwebmienphi
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
get_template_part('templates/blocks/page-title', null, []);
?>

<div class="page page-standard">
	<div class="container page-standard__container">
		<div class="row page-standard__row">
			<div class="page-standard__col page-standard__left col-lg-8 col-md-12 col-12">
				<main id="primary" class="site-main">
					<?php
					do_action( 'twmp_before_content_page' );
					while (have_posts()) :
						the_post();

						get_template_part('template-parts/content', 'page');

						// If comments are open or we have at least one comment, load up the comment template.
						if (comments_open() || get_comments_number()) :
							comments_template();
						endif;

					endwhile; // End of the loop.
					?>

				</main><!-- #main -->
			</div>
			<div class="page-standard__col page-standard__right col-lg-4 col-md-12 col-12">
				<?php get_sidebar(); ?>
			</div>
		</div>
	</div>
</div>
<?php

get_footer();
