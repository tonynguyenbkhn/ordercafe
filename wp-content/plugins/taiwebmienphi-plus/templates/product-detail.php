<?php
    $twmp_theme_info = get_field('theme_info', get_the_ID());
?>

<div class="product-single-area">
    <div class="w-100 product-sidebar">
        <div class="sidebar-item item-attributes">
            <?php echo wp_kses_post($twmp_theme_info); ?>
        </div>
    </div>
</div>