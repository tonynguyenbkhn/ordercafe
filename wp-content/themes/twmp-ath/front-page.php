<?php

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