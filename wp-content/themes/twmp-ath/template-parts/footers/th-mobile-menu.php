<?php

if (!defined('ABSPATH')) {
    exit;
}

$menu_class     = \TWMP_THEME\Inc\Menus_Theme::get_instance();
$header_menu_id = $menu_class->get_menu_id('primary');
$header_menus   = wp_get_nav_menu_items($header_menu_id);

if (! empty($header_menus) && is_array($header_menus)) {
?>
    <div class="th-menu-wrapper" data-block="thmobilemenu">
        <div class="th-menu-area text-center">
            <button class="th-menu-toggle"><?php echo twmp_get_svg_icon('close') ?></button>
            <div class="mobile-logo">
                <?php get_template_part('template-parts/headers/logo', null, []); ?>
            </div>
            <div class="th-mobile-menu">
                <ul>
                <?php foreach ($header_menus as $menu_item) {
					if (! $menu_item->menu_item_parent) {

						$child_menu_items   = $menu_class->get_child_menu_items($header_menus, $menu_item->ID);
						$has_children       = ! empty($child_menu_items) && is_array($child_menu_items);
						$has_sub_menu_class = ! empty($has_children) ? 'has-submenu' : '';
						$link_target        = ! empty($menu_item->target) && '_blank' === $menu_item->target ? '_blank' : '_self';
						if (! $has_children) { ?>
							<li class="">
								<a
									href="<?php echo esc_url($menu_item->url); ?>"
									target="<?php echo esc_attr($link_target); ?>"
									title="<?php echo esc_attr($menu_item->title); ?>">
									<?php echo esc_html($menu_item->title); ?>                                    
								</a>
							</li>
						<?php } else { ?>
							<li class="menu-item-has-children th-item-has-children">
								<a
									href="<?php echo esc_url($menu_item->url); ?>"
									target="<?php echo esc_attr($link_target); ?>"
									title="<?php echo esc_attr($menu_item->title); ?>">
									<?php echo esc_html($menu_item->title); ?>
                                    <span class="th-mean-expand"></span>
								</a>
								<ul class="sub-menu th-submenu d-none">
									<?php foreach ($child_menu_items as $child_menu_item) {
										$link_target = ! empty($child_menu_item->target) && '_blank' === $child_menu_item->target ? '_blank' : '_self';
									?>
										<li>
											<a
												href="<?php echo esc_url($child_menu_item->url); ?>"
												target="<?php echo esc_attr($link_target); ?>"
												title="<?php echo esc_attr($child_menu_item->title); ?>"><?php echo esc_html($child_menu_item->title); ?>
											</a>
										</li>
									<?php } ?>
								</ul>
							</li>
						<?php }
						?>

				<?php }
				}
				?>
                </ul>
            </div>
        </div>
        <div class="th-menu-wrapper__overlay" data-th-menu-trigger></div>
    </div>

<?php } ?>