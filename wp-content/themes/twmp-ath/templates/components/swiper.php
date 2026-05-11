<?php

if (!defined('ABSPATH')) {
	exit;
}

$data = wp_parse_args(
	$args,
	array(
		'class'                  => '',
		'data_block'             => '',
		'settings' =>            [
			'autoPlay' => false,
			'pagination' => true,
			'prevNextButtons' => true,
			'prevSvgButton' => 'arrow-left',
			'nextSvgButton' => 'arrow-right'
		],
		'enable_container'       => false,
		'container_class'        => 'swiper-container',
		'items'                  => array()
	)
);

$_class  = 'js-swiper swiper';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_settings = $data['settings'] ?? [];

if (!empty($data['items'])) :
	ob_start();
?>
	<div class="swiper-wrapper">
		<?php foreach ($data['items'] as $index => $item) :
			$_item_class = 'swiper-slide';
			$_item_class .= 0 === ($index % 2) ? ' is-even' : ' is-odd';

			if (!empty($item['class'])) :
				$_item_class .= esc_attr(' ' . $item['class']);
			endif;

			if (!empty($item['lazyload'])) :
				$_item_class .= ' is-not-loaded';
			endif;
		?>
			<div class="<?php echo esc_attr($_item_class); ?>">
				<?php if (!empty($item['lazyload'])) : ?>
					<noscript>
						<?php echo $item['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</noscript>
				<?php else : ?>
					<?php echo $item['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if (!empty($_settings['pagination'])) : ?>
		<div class="swiper-pagination"></div>
	<?php endif; ?>
	<?php if (!empty($_settings['prevNextButtons'])) : ?>
		<div class="swiper-button swiper-button-prev">
			<?php if (!empty($data['settings']['prevSvgButton'])) {
				echo twmp_get_svg_icon($data['settings']['prevSvgButton']);
			} ?>
		</div>
		<div class="swiper-button swiper-button-next">
			<?php if (!empty($data['settings']['nextSvgButton'])) {
				echo twmp_get_svg_icon($data['settings']['nextSvgButton']);
			} ?>
		</div>
	<?php endif; ?>
	<?php
	$slide_html = ob_get_clean();
	?>
	<div class="<?php echo esc_attr($_class); ?>" data-settings='<?php echo json_encode($_settings); ?>' <?php if (!empty($data['data_block'])) : ?> data-block="<?php echo esc_attr($data['data_block']); ?>" <?php endif; ?>>
		<?php if (!empty($data['enable_container'])) : ?>
			<div class="<?php echo esc_attr($data['container_class']); ?>">
				<?php echo $slide_html; ?>
			</div>
		<?php else : ?>
			<?php echo $slide_html; ?>
		<?php endif; ?>
	</div>
<?php else : ?>
<?php
endif;
