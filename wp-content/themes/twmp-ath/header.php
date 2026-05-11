<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<!-- Font Google -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,100..900;1,100..900&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<header id="header-sticky">
		<div class="header__main position-relative">
			<div class="container header__container">
				<div class="header__row">
					<div class="flex-auto header__col header__logo">
						<?php get_template_part('template-parts/headers/logo', null, []); ?>
					</div>
					<div class="flex-auto header__col header__nav fw-normal">
						<?php get_template_part('template-parts/headers/main-nav', null, []); ?>
					</div>
					<div class="flex-auto header__col header__actions">
						<div class="header__menu-icons">
							<div class="">
								<?php get_template_part('template-parts/headers/icon-search', null, []); ?>
							</div>
							<div class="th-menu-toggle"><?php echo twmp_get_svg_icon('menu') ?></div>
							<div>
								<?php get_template_part('templates/components/button', null, [
									'class' => 'bg-primary-500 text-white typo-system-button button-default',
									'button_text' => esc_html__('Get Started', 'twmp-ath'),
									'button_url' => '#',
									'button_link_target' => '_blank',
								]); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>

	<?php do_action('twmp_after_header'); ?>