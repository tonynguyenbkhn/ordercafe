<?php

if (!defined('ABSPATH')) {
	exit;
}

$data = wp_parse_args($args, [
	'class' => '',
	'button_attrs' => '',
	'button_text' => esc_html__('Read More', 'twmp-ath'),
	'button_sub_text' => '',
	'button_url'  => '',
	'button_link_target' => '_self',
	'button_link_rel' => '',
	'svg_icon_before' => '',
	'svg_icon_after' => '',
]);

$_class = '';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';
$_class .= !empty($data['svg_icon_before']) ? ' has-icon has-before-icon' : '';
$_class .= !empty($data['svg_icon_after']) ? ' has-icon has-after-icon' : '';

ob_start(); ?>
<?php if (!empty($data['svg_icon_before'])) : ?>
	<span class="icon pe-none" aria-hidden="true"><?php echo $data['svg_icon_before']; ?></span>
<?php endif; ?>
<span class="text pe-none"><?php echo esc_html($data['button_text']); ?></span>
<?php if (!empty($data['svg_icon_after'])) : ?>
	<span class="icon pe-none" aria-hidden="true"><?php echo $data['svg_icon_after']; ?></span>
<?php endif; ?>
<?php $button_html = ob_get_clean();

if (!empty($data['button_url'])) : ?>
	<a title="<?php echo esc_attr($data['button_text'] . ' ' . $data['button_sub_text']); ?>" class="<?php echo esc_attr($_class); ?>"
		<?php if (!empty($data['button_attrs'])) : echo ' ' . esc_attr($data['button_attrs']);
		endif; ?>
		href="<?php echo esc_url($data['button_url']); ?>"
		target="<?php echo esc_attr($data['button_link_target']); ?>"
		<?php if (!empty($data['button_link_rel'])) : ?> rel="<?php echo esc_attr($data['button_link_rel']); ?>" <?php endif; ?>>
		<?php echo $button_html; ?>
	</a>
<?php else : ?>
	<button class="<?php echo esc_attr($_class); ?>" <?php if (!empty($data['button_attrs'])) : echo ' ' . esc_attr($data['button_attrs']);
														endif; ?>>
		<?php echo $button_html; ?>
	</button>
<?php endif;
