<?php

/**
 * Template Name: Flexible
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

?>
<div class="flexible-content">
	<?php
	get_template_part('templates/content/flexible');
	?>
</div>
<?php
get_footer();
