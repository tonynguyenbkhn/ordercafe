<?php

if (!defined('ABSPATH')) {
    exit;
}

$menu_class     = \TWMP_THEME\Inc\Menus_Theme::get_instance();
$header_menu_id = $menu_class->get_menu_id('primary');
$header_menus   = wp_get_nav_menu_items($header_menu_id);
$current_id     = get_queried_object_id();
if (! empty($header_menus) && is_array($header_menus)) {
?>
	<div class="main-menu" id="primary-menu">
		<nav id="mobile-menu">
			<ul class="reset">
				<?php foreach ($header_menus as $menu_item) {
					if (! $menu_item->menu_item_parent) {
						$child_menu_items   = $menu_class->get_child_menu_items($header_menus, $menu_item->ID);
						$has_children       = ! empty($child_menu_items) && is_array($child_menu_items);
						$has_sub_menu_class = ! empty($has_children) ? 'has-submenu' : '';
						$link_target        = ! empty($menu_item->target) && '_blank' === $menu_item->target ? '_blank' : '_self';
						$is_active          = ($menu_item->object_id == $current_id) ? 'active' : '';
                        $admin_classes      = ! empty( $menu_item->classes ) ? implode( ' ', $menu_item->classes ) : '';
                        $li_classes         = trim( "$admin_classes $is_active" );
						if (! $has_children) { ?>
							<li class="menu-item <?php echo esc_attr($is_active); ?>">
								<a
									href="<?php echo esc_url($menu_item->url); ?>"
									target="<?php echo esc_attr($link_target); ?>"
									title="<?php echo esc_attr($menu_item->title); ?>">
									<?php echo esc_html($menu_item->title); ?>
								</a>
							</li>
						<?php } else { $is_active_parent = ($menu_item->object_id == $current_id) ? 'active' : '';?>
							<li class="menu-item  sub-menu-<?php echo esc_attr(sanitize_title($menu_item->title)); ?> <?php echo esc_attr( $li_classes ); ?> <?php echo esc_attr("has-dropdown $is_active_parent"); ?>">
								<a
									href="<?php echo esc_url($menu_item->url); ?>"
									target="<?php echo esc_attr($link_target); ?>"
									title="<?php echo esc_attr($menu_item->title); ?>">
									<?php echo esc_html($menu_item->title); ?>
								</a>
								<ul class="submenu reset">
									<?php foreach ($child_menu_items as $child_menu_item) {
										$link_target = ! empty($child_menu_item->target) && '_blank' === $child_menu_item->target ? '_blank' : '_self';
										$is_active_child  = ($child_menu_item->object_id == $current_id) ? 'active' : '';
									?>
										<li class="<?php echo esc_attr($is_active_child); ?>">
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
		</nav>
	</div>

<?php } ?>