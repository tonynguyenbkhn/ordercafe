<?php

if (! defined('ABSPATH')) {
	exit;
}

$data = wp_parse_args(
	$args,
	[
		'name'        => '',
		'position'    => '',
		'description' => '',
		'image_id'    => 0,
		'image_size'  => 'large',
		'lazyload'    => false,
		'url'         => '',
	]
);

$name_html = esc_html($data['name']);
?>

<article class="team-card">
	<?php if (! empty($data['image_id'])) : ?>
		<div class="team-card__media">
			<?php
			get_template_part(
				'templates/components/image',
				null,
				[
					'image_id'    => $data['image_id'],
					'image_size'  => $data['image_size'],
					'lazyload'    => $data['lazyload'],
					'class'       => 'team-card__image-wrap image--cover image--default',
					'image_class' => 'team-card__image',
					'alt'         => $data['name'],
				]
			);
			?>
		</div>
	<?php endif; ?>

	<div class="team-card__body">
		<?php if (! empty($data['name'])) : ?>
			<h3 class="team-card__name">
				<?php if (! empty($data['url'])) : ?>
					<a href="<?php echo esc_url($data['url']); ?>"><?php echo $name_html; ?></a>
				<?php else : ?>
					<?php echo $name_html; ?>
				<?php endif; ?>
			</h3>
		<?php endif; ?>

		<?php if (! empty($data['position'])) : ?>
			<p class="team-card__position"><?php echo esc_html($data['position']); ?></p>
		<?php endif; ?>

		<?php if (! empty($data['description'])) : ?>
			<div class="team-card__description">
				<?php echo wp_kses_post($data['description']); ?>
			</div>
		<?php endif; ?>
	</div>
</article>
