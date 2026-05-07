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
		'button_text'      => '',
		'button_link'      => '',
		'artists'          => [],
		'enable_container' => false,
	]
);

$_class = 'team-section';
$_class .= ! empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_class_container = 'container';
$_class_container .= ! empty($data['class_container']) ? esc_attr(' ' . $data['class_container']) : '';

$artist_ids = is_array($data['artists']) ? array_filter(array_map('absint', $data['artists'])) : [];
$slides = [];

foreach ($artist_ids as $artist_id) {
	$artist_post = get_post($artist_id);

	if (! $artist_post instanceof WP_Post || 'publish' !== $artist_post->post_status) {
		continue;
	}

	$item_args = [
		'name'        => get_the_title($artist_post),
		'position'    => get_the_excerpt($artist_post),
		'description' => wp_trim_words(wp_strip_all_tags($artist_post->post_content), 18, '...'),
		'image_id'    => get_post_thumbnail_id($artist_id),
		'image_size'  => 'large',
		'lazyload'    => false,
		'url'         => get_post_meta($artist_id, '_artist_url', true),
	];

	ob_start();
	get_template_part('templates/sections/team/item', null, $item_args);
	$slides[] = [
		'content' => ob_get_clean(),
		'class'   => 'team-section__slide',
	];
}

$has_intro = ! empty($data['title']) || ! empty($data['description']) || (! empty($data['button_text']) && ! empty($data['button_link']));

if (! $has_intro && empty($slides)) {
	return;
}
?>

<section class="<?php echo esc_attr($_class); ?>" <?php if (! empty($data['id'])) : ?> id="<?php echo esc_attr($data['id']); ?>" <?php endif; ?>>
	<?php if ($data['enable_container']) : ?>
		<div class="<?php echo esc_attr($_class_container); ?>">
		<?php endif; ?>

		<div class="team-section__shell">
			<div class="team-section__header">
				<div class="team-section__intro">
					<?php
					get_template_part(
						'templates/components/heading',
						null,
						[
							'title_class'       => 'team-section__title',
							'description_class' => 'team-section__description',
							'class'             => 'team-section__heading',
							'title'             => $data['title'],
							'description'       => $data['description'],
						]
					);
					?>

					<?php if (! empty($data['button_text']) && ! empty($data['button_link'])) : ?>
						<div class="team-section__cta">
							<?php
							get_template_part(
								'templates/components/button',
								null,
								[
									'class'              => 'team-section__button button-medium typo-system-button',
									'button_text'        => $data['button_text'],
									'button_url'         => $data['button_link'],
									'button_link_target' => '_self',
									'svg_icon_after'     => twmp_get_svg_icon('arrow-right'),
								]
							);
							?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if (! empty($slides)) : ?>
				<div class="team-section__slider-wrap position-relative">
					<div class="swiper-button swiper-button-prev">
						<?php if (! empty($data['settings']['prevSvgButton'])) {
							echo twmp_get_svg_icon($data['settings']['prevSvgButton']);
						} ?>
					</div>
					<div class="swiper-button swiper-button-next">
						<?php if (! empty($data['settings']['nextSvgButton'])) {
							echo twmp_get_svg_icon($data['settings']['nextSvgButton']);
						} ?>
					</div>
					<?php
					get_template_part(
						'templates/components/swiper',
						null,
						[
							'class'            => 'team-section__swiper',
							'data_block'       => 'team',
							'enable_container' => false,
							'settings'         => [
								'autoPlay'        => false,
								'pagination'      => false,
								'prevNextButtons' => false,
								'slidesPerView'   => 1.15,
								'spaceBetween'    => 32,
								'breakpoints'     => [
									640  => [
										'slidesPerView' => 1.4,
										'spaceBetween'  => 36,
									],
									992  => [
										'slidesPerView' => 2.3,
										'spaceBetween'  => 40,
									],
									1200 => [
										'slidesPerView' => 3.3,
										'spaceBetween'  => 48,
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
