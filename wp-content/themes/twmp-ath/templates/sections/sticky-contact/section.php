<?php
$data = wp_parse_args(
	$args,
	[
		'items'     => [],
		'position' 	=> 'left'
	]
);

$items = $data['items'];

$position = !empty($data['position']) ? 'sticky-contact__' . $data['position'] : 'sticky-contact__left';
if (!empty($items)) : ?>
	<div class="sticky-contact position-fixed <?php echo esc_attr($position); ?>">
		<ul class="sticky-contact__items js-sticky-contact-items m-0 p-0">
			<?php foreach ($items as $item) : ?>
				<?php if (!empty($item)) : ?>
					<?php
					$icon = !empty($item['type']) ? $item['type'] : '';
					$url = !empty($item['url']) ? $item['url'] : '';
					$title = '';
					$item_class = "sticky-contact__item";
					$item_class .= " sticky-contact__item--" . $icon;
					$item_class .= " position-relative rounded-circle mt-2";
					?>

					<li class="<?php echo esc_html($item_class); ?>">
						<a rel="nofollow" href="<?php echo esc_url_raw($url); ?>" class="sticky-contact__url" title="<?php echo esc_attr($title); ?>" target="_blank">
							<?php if (!empty($icon)) : ?>
								<span class="sticky-contact__icon d-flex justify-content-center align-items-center" aria-hidden="true">
									<?php echo twmp_get_svg_icon('sk-'.$icon); ?>
								</span>
							<?php endif; ?>

							<?php if (!empty($title)) : ?>
								<span class="sticky-contact__title text-nowrap position-absolute d-flex align-items-center text-white rounded-pill px-3 mx-1">
									<?php echo esc_html($title); ?>
								</span>
							<?php endif; ?>
						</a>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif;
