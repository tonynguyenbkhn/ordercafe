<?php

if (! defined('ABSPATH')) {
	exit;
}

$data = wp_parse_args(
	$args,
	[
		'id'               => '',
		'class'            => '',
		'class_container'  => '',
		'title'            => '',
		'description'      => '',
		'gallery'          => [],
		'enable_container' => false,
	]
);

$_class = 'logo-slider-section';
$_class .= ! empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_class_container = 'container';
$_class_container .= ! empty($data['class_container']) ? esc_attr(' ' . $data['class_container']) : '';

$gallery_ids = is_array($data['gallery']) ? array_values(array_filter(array_map('absint', $data['gallery']))) : [];
$slides = [];

foreach ($gallery_ids as $image_id) {
	if (! $image_id) {
		continue;
	}

	ob_start();
	get_template_part(
		'templates/sections/logo-slider/item',
		null,
		[
			'image_id'   => $image_id,
			'image_size' => 'full',
			'lazyload'   => false,
		]
	);
	$item_content = ob_get_clean();

	if ('' === trim((string) $item_content)) {
		continue;
	}

	$slides[] = [
		'content' => $item_content,
		'class'   => 'logo-slider-section__slide',
	];
}

$has_intro = ! empty($data['title']) || ! empty($data['description']);

if (! $has_intro && empty($slides)) {
	return;
}
?>

<section class="<?php echo esc_attr($_class); ?>" <?php if (! empty($data['id'])) : ?> id="<?php echo esc_attr($data['id']); ?>" <?php endif; ?>>
	<?php if ($data['enable_container']) : ?>
		<div class="<?php echo esc_attr($_class_container); ?>">
		<?php endif; ?>

		<div class="logo-slider-section__shell">
			<?php if ($has_intro) : ?>
				<div class="logo-slider-section__header">
					<?php
					get_template_part(
						'templates/components/heading',
						null,
						[
							'title_class'       => 'logo-slider-section__title',
							'description_class' => 'logo-slider-section__description',
							'class'             => 'logo-slider-section__heading',
							'title'             => $data['title'],
							'description'       => $data['description'],
						]
					);
					?>
				</div>
			<?php endif; ?>

			<?php if (! empty($slides)) : ?>
				<div class="logo-slider-section__slider-wrap position-relative">
					<?php
					get_template_part(
						'templates/components/swiper',
						null,
						[
							'class'            => 'logo-slider-section__swiper',
							'data_block'       => 'logo-slider',
							'enable_container' => false,
							'settings'         => [
								'autoPlay'        => false,
								'pagination'      => false,
								'prevNextButtons' => false,
								'slidesPerView'   => 6.5,
								'spaceBetween'    => 32,
								'breakpoints'     => [
									640  => [
										'slidesPerView' => 3.2,
                                        'spaceBetween'    => 32,
									],
									992  => [
										'slidesPerView' => 4.2,
                                        'spaceBetween'    => 32,
									],
									1200 => [
										'slidesPerView' => 7.5,
                                        'spaceBetween'    => 32,
									],
								],
							],
							'items'            => $slides,
						]
					);
					?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ($data['enable_container']) : ?>
		</div>
	<?php endif; ?>
</section>
