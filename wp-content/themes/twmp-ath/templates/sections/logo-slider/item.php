<?php

if (! defined('ABSPATH')) {
	exit;
}

$data = wp_parse_args(
	$args,
	[
		'image_id'   => 0,
		'image_size' => 'full',
		'lazyload'   => false,
		'alt'        => '',
	]
);
?>

<div class="logo-slider-card">
	<?php if (! empty($data['image_id'])) : ?>
		<div class="logo-slider-card__media">
			<?php
			get_template_part(
				'templates/components/image',
				null,
				[
					'image_id'    => $data['image_id'],
					'image_size'  => $data['image_size'],
					'lazyload'    => $data['lazyload'],
					'class'       => 'logo-slider-card__image-wrap image--contain image--default',
					'image_class' => 'logo-slider-card__image',
					'alt'         => ! empty($data['alt']) ? $data['alt'] : get_the_title($data['image_id']),
				]
			);
			?>
		</div>
	<?php endif; ?>
</div>
