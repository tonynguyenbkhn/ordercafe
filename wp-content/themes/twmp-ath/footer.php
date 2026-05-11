<?php

if (!defined('ABSPATH')) {
    exit;
}

get_template_part('templates/blocks/album-feedback', null, ['enable_container' => false]);

if (is_active_sidebar('footer-top')) {
?>
    <div class="footer-top">
        <?php dynamic_sidebar('footer-top'); ?>
    </div><!-- End Footer -->
<?php
}
?>
<?php
if (is_active_sidebar('footer-primary')) {
?>
    <footer id="colophon" class="site-footer">
        <?php dynamic_sidebar('footer-primary'); ?>
    </footer><!-- End Footer -->
<?php
}
?>
<?php
if (is_active_sidebar('footer-absolute')) {
?>
    <div class="footer-absolute">
        <?php dynamic_sidebar('footer-absolute'); ?>
    </div>
<?php
}
?>
<?php
$dataStickyContact['items'] = get_field('sticky_links', 'option') ? get_field('sticky_links', 'option') : [];
get_template_part('templates/sections/back-to-top/section', null, []);
get_template_part('templates/sections/sticky-contact/section', null, $dataStickyContact);
get_template_part('template-parts/footers/modal-search-form', null, []);
get_template_part('template-parts/footers/modal-popup-welcome', null, []);
// get_template_part('template-parts/footers/mini-cart', null, []);
// get_template_part('templates/blocks/menu-mobile-footer', null, []);
?>

<?php wp_footer(); ?>

</body>

</html>
