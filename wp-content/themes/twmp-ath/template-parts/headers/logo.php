<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args($args, [
	'class' => '',
]);

$_class = 'logo site-logo';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$logo_url = (function_exists('the_custom_logo') && get_theme_mod('custom_logo')) ? wp_get_attachment_image_src(get_theme_mod('custom_logo'), 'full') : false;
$logo_url = ($logo_url) ? $logo_url[0] : TWMP_IMG_URI . '/logo.png';

if (empty($logo_url)) {
    return;
}

$attr = array(
    'class' => 'is-logo-image',
    'alt'   => get_bloginfo('name', 'display'),
    'src'   => $logo_url,
);

$html_attr = '';
foreach ($attr as $name => $value) {
    $html_attr .= " $name=" . '"' . $value . '"';
}

echo sprintf(
    '<div class="%1$s">
        <a href="%2$s" rel="home">
            <img %3$s />
        </a>
    </div>',
	$_class,
    esc_url(home_url('/')),
    $html_attr
);