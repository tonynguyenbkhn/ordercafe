<?php

/**
 * Template Name: Full Width Without Title
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
get_template_part('templates/blocks/page-title', null, ['class' => 'reverse-title', 'show_title'=>false]);
?>
<div class="page page-standard">
	<div class="container page-standard__container">
		<div class="row page-standard__row">
			<div class="page-standard__col page-standard__left col-lg-12 col-md-12 col-12">
				<main id="primary" class="site-main">
					<?php
					do_action( 'twmp_before_content_page' );
					while (have_posts()) :
						the_post();
						get_template_part('template-parts/content', 'page');
					endwhile; // End of the loop.
					?>
				</main><!-- #main -->
			</div>
		</div>
	</div>
</div>
<?php

get_footer();
