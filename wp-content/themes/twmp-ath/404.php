<?php

/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package taiwebmienphi
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>


<div class="not-found-page">
	<div class="container">
		<div class="not-found-content">
			<h1 class="not-found-title"><?php esc_html_e('404', 'twmp-ath'); ?></h1>
			<p class="not-found-message"><?php esc_html_e('Oops! The page you are looking for doesn’t exist.', 'twmp-ath'); ?></p>
			<a href="<?php echo esc_url(home_url('/')); ?>" class="not-found-btn">
				<?php esc_html_e('Back to Home', 'twmp-ath'); ?>
			</a>
		</div>
	</div>
</div>

<?php
get_footer();
