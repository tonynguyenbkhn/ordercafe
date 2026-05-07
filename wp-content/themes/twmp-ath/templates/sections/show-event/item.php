<?php

if (! defined('ABSPATH')) {
	exit;
}

$data = wp_parse_args(
	$args,
	[
		'product_id'   => 0,
		'title'        => '',
		'badges'       => [],
		'short_info'   => '',
		'location'     => '',
		'description'  => '',
		'date_day'     => '',
		'date_weekday' => '',
		'date_month'   => '',
		'date_year'    => '',
		'image_id'     => 0,
		'image_size'   => 'large',
		'lazyload'     => true,
		'permalink'    => '',
		'featured'     => false,
		'theme_class'  => '',
	]
);

$badge_rows = is_array($data['badges']) ? array_values(array_filter($data['badges'])) : [];

$_class = 'event-card';
$_class .= ! empty($data['featured']) ? ' is-featured' : '';
$_class .= ! empty($data['theme_class']) ? esc_attr(' ' . $data['theme_class']) : '';
?>

<article class="<?php echo esc_attr($_class); ?>">
	<div class="event-card__media">
		<?php if (! empty($data['image_id'])) : ?>
			<?php
			get_template_part(
				'templates/components/image',
				null,
				[
					'image_id'    => $data['image_id'],
					'image_size'  => $data['image_size'],
					'lazyload'    => $data['lazyload'],
					'class'       => 'event-card__image-wrap image--cover image--default',
					'image_class' => 'event-card__image',
					'alt'         => $data['title'],
				]
			);
			?>
		<?php endif; ?>

		<div class="event-card__overlay" aria-hidden="true"></div>
	</div>

	<div class="event-card__top">
		<?php if (! empty($badge_rows)) : ?>
			<div class="event-card__badges">
				<?php foreach ($badge_rows as $badge) : ?>
					<?php
					$badge_text = isset($badge['text']) ? trim((string) $badge['text']) : '';
					$badge_style = isset($badge['style']) ? trim((string) $badge['style']) : 'orange';

					if ('' === $badge_text) {
						continue;
					}
					?>
					<span class="ath-badge ath-badge--<?php echo esc_attr($badge_style); ?>"><?php echo esc_html($badge_text); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if (! empty($data['date_day']) || ! empty($data['date_weekday']) || ! empty($data['date_month']) || ! empty($data['date_year'])) : ?>
			<div class="event-card__date">
				<?php if (! empty($data['date_day'])) : ?><span class="event-card__date-day"><?php echo esc_html($data['date_day']); ?></span><?php endif; ?>
				<?php if (! empty($data['date_weekday'])) : ?><span class="event-card__date-weekday"><?php echo esc_html($data['date_weekday']); ?></span><?php endif; ?>
				<?php if (! empty($data['date_month']) || ! empty($data['date_year'])) : ?><span class="event-card__date-month"><?php echo esc_html(trim($data['date_month'] . ', ' . $data['date_year'], ', ')); ?></span><?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="event-card__body">
		<?php if (!empty($data['title']) && 1 === 0) : ?>
			<h3 class="event-card__title"><?php echo esc_html($data['title']); ?></h3>
		<?php endif; ?>

		<?php if (! empty($data['short_info'])) : ?>
			<p class="event-card__short-info"><?php echo esc_html($data['short_info']); ?></p>
		<?php endif; ?>

		<?php if (! empty($data['location'])) : ?>
			<div class="event-card__location"><?php echo esc_html($data['location']); ?></div>
		<?php endif; ?>

		<?php if (! empty($data['description']) || ! empty($data['permalink'])) : ?>
			<div class="event-card__more">
				<?php if (! empty($data['description'])) : ?>
					<div class="event-card__description"><?php echo esc_html($data['description']); ?></div>
				<?php endif; ?>

				<div class="event-card__actions">
					<?php if (function_exists('twmp_render_cart_button') && ! empty($data['product_id'])) : ?>
						<div class="event-card__action event-card__action--book">
							<?php
							global $product;
							$previous_product = $product ?? null;
							$product = function_exists('wc_get_product') ? wc_get_product($data['product_id']) : null;

							if ($product) {
								twmp_render_cart_button();
							}

							$product = $previous_product;
							?>
						</div>
					<?php endif; ?>

					<?php if (! empty($data['permalink'])) : ?>
						<div class="event-card__action event-card__action--view">
							<?php
							get_template_part(
								'templates/components/button',
								null,
								[
									'class'              => 'event-card__view-button button-normal typo-system-button button-default',
									'button_text'        => esc_html__('View Detail', 'twmp-ath'),
									'button_url'         => $data['permalink'],
									'button_link_target' => '_self',
									'svg_icon_after'     => twmp_get_svg_icon('arrow-right'),
								]
							);
							?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</article>
