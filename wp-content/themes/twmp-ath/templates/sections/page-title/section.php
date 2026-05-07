<?php
$data = wp_parse_args($args, [
	'class'             => '',
	'id'                => '',
	'title'             => '',
	'text'              => '',
	'description'       => '',
	'show_title'        => true,
	'tag_h1'            => true,
	'show_breadcrumbs'  => true,
]);
$_class  = 'page__title-area';
$_class .= ! empty($data['class']) ? esc_attr(' ' . $data['class']) : '';
$title = '' !== trim((string) $data['title']) ? (string) $data['title'] : (string) $data['text'];
?>

<section class="<?php echo esc_attr($_class); ?>" <?php if (! empty($data['id'])) : ?>id="<?php echo esc_attr($data['id']); ?>" <?php endif; ?>>
	<div class="container">
		<div class="page__title-content">
			<?php if ($data['show_breadcrumbs']) : ?>
				<div class="breadcrumbs">
					<div class="d-flex items-center gap-8">
						<?php echo twmp_get_svg_icon('home'); ?>
						<div class="text-system-content-1">
							<?php twmp_breadcrumbs(); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
			<?php if ($data['show_title']) : ?>
				<?php if ('' !== trim($title)): ?>
					<?php if ($data['tag_h1']) : ?>
						<h1 class="page__title"><?php echo esc_html($title); ?></h1>
					<?php else : ?>
						<div class="page__title h1"><?php echo esc_html($title); ?></div>
					<?php endif; ?>
				<?php elseif (is_archive()): ?>
					<?php $data['tag_h1'] ? the_archive_title('<h1 class="page__title">', '</h1>') : the_archive_title('<div class="page__title h1">', '</div>'); ?>
				<?php elseif (is_singular()): ?>
					<?php the_title('<h1 class="page__title">', '</h1>'); ?>
				<?php elseif (is_tag()): ?>
					<?php $data['tag_h1'] ? the_archive_title('<h1 class="page__title">', '</h1>') : the_archive_title('<div class="page__title h1">', '</div>'); ?>
				<?php endif; ?>
			<?php endif; ?>
			<?php if (! empty($data['description'])) : ?>
				<div class="page__description">
					<?php echo wp_kses_post(wpautop($data['description'])); ?>
				</div>
			<?php endif; ?>
		</div>
</section>