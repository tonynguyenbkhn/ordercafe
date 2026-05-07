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
		'items'            => [],
		'enable_container' => false,
	]
);

$_class = 'testimonials-section';
$_class .= ! empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_class_container = 'container';
$_class_container .= ! empty($data['class_container']) ? esc_attr(' ' . $data['class_container']) : '';

$testimonial_items = is_array($data['items']) ? array_values($data['items']) : [];
$slides = [];

foreach ($testimonial_items as $item) {
	if (! is_array($item)) {
		continue;
	}

	$avatar_id = isset($item['avatar']) ? absint($item['avatar']) : 0;
	$name = isset($item['name']) ? (string) $item['name'] : '';
	$school = isset($item['school']) ? (string) $item['school'] : '';
	$content = isset($item['content']) ? (string) $item['content'] : '';

	if ('' === trim($name . $school . wp_strip_all_tags($content)) && ! $avatar_id) {
		continue;
	}

	ob_start();
	get_template_part(
		'templates/sections/testimonials/item',
		null,
		[
			'avatar_id'  => $avatar_id,
			'avatar_alt' => $name,
			'name'       => $name,
			'school'     => $school,
			'content'    => $content,
			'lazyload'   => false,
		]
	);
	$item_content = ob_get_clean();

	if ('' === trim((string) $item_content)) {
		continue;
	}

	$slides[] = [
		'content' => $item_content,
		'class'   => 'testimonials-section__slide',
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

		<div class="testimonials-section__shell">
			<?php if ($has_intro) : ?>
				<div class="testimonials-section__header">
					<?php
					get_template_part(
						'templates/components/heading',
						null,
						[
							'title_class'       => 'testimonials-section__title',
							'description_class' => 'testimonials-section__description',
							'class'             => 'testimonials-section__heading',
							'title'             => $data['title'],
							'description'       => $data['description'],
						]
					);
					?>
				</div>
			<?php endif; ?>

			<?php if (! empty($slides)) : ?>
				<div class="testimonials-section__slider-wrap position-relative">
					<?php
					get_template_part(
						'templates/components/swiper',
						null,
						[
							'class'            => 'testimonials-section__swiper',
							'data_block'       => 'testimonials',
							'enable_container' => false,
							'settings'         => [
								'autoPlay'        => false,
								'pagination'      => [
									'el'        => '.swiper-pagination',
									'type'      => 'progressbar',
									'clickable' => false,
								],
								'prevNextButtons' => false,
								'slidesPerView'   => 1.05,
								'centeredSlides'  => true,
								'spaceBetween'    => 24,
								'breakpoints'     => [
									640  => [
										'slidesPerView' => 1.2,
										'spaceBetween'  => 24,
									],
									992  => [
										'slidesPerView' => 2.15,
										'spaceBetween'  => 24,
									],
									1200 => [
										'slidesPerView' => 2.5,
										'spaceBetween'  => 24,
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
