<?php

if (!defined('ABSPATH')) {
    exit;
}

$menu_title             = __('Menu', 'twmp-ath');
$close_menu_button_text = __('Close', 'twmp-ath');

?>
<div class="slideout-menu" data-block="slideout-menu">
	<div class="slideout-menu__wrapper">
		<div class="slideout-menu__header">
			<div class="slideout-menu__header-wrapper">
				<div class="slideout-menu__logo">
					<img src="<?php echo TWMP_IMG_URI . '/logo.png' ?>" width="50px" height="50px" alt="">
				</div>
			</div>
		</div>
		<div class="slideout-menu__inner">
			<div class="slideout-menu__content">
				<ul class="slideout-menu__menu">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => null,
							'fallback_cb'    => false,
							'items_wrap'     => '%3$s',
						)
					);
					?>
				</ul>
			</div>
			<div class="slideout-menu__footer">
				<div class="" data-slideout-menu-trigger><?php echo twmp_get_svg_icon('close'); ?></div>
			</div>
		</div>
	</div>
	<div class="slideout-menu__overlay" data-slideout-menu-trigger></div>
</div>