<?php

if (! defined('ABSPATH')) {
	exit;
}

$data = wp_parse_args(
	$args,
	[
		'avatar_id'  => 0,
		'avatar_alt' => '',
		'name'       => '',
		'school'     => '',
		'content'    => '',
		'lazyload'   => false,
	]
);
?>

<article class="testimonial-card-wrapper">
	<div class="testimonial-card">
		<?php if (! empty($data['avatar_id'])) : ?>
			<div class="testimonial-card__avatar">
				<?php
				get_template_part(
					'templates/components/image',
					null,
					[
						'image_id'    => $data['avatar_id'],
						'image_size'  => 'thumbnail',
						'lazyload'    => $data['lazyload'],
						'class'       => 'testimonial-card__avatar-wrap image--cover image--default',
						'image_class' => 'testimonial-card__avatar-image',
						'alt'         => $data['avatar_alt'],
					]
				);
				?>
			</div>
		<?php endif; ?>

		<div class="testimonial-card__body">
			<?php if (! empty($data['name']) || ! empty($data['school'])) : ?>
				<div class="testimonial-card__meta">
					<?php if (! empty($data['name'])) : ?>
						<h3 class="testimonial-card__name"><?php echo esc_html($data['name']); ?></h3>
					<?php endif; ?>

					<?php if (! empty($data['school'])) : ?>
						<p class="testimonial-card__school"><?php echo esc_html($data['school']); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php if (! empty($data['content'])) : ?>
		<div class="testimonial-card__content">
			<?php echo wp_kses_post($data['content']); ?>
		</div>
	<?php endif; ?>
</article>