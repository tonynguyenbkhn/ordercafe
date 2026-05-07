<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<form method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label>
        <span class="screen-reader-text"><?php echo _x('Search for:', 'label', 'twmp-ath') ?></span>
        <input type="search" class="search-field" placeholder="<?php echo _x('Search &hellip;', 'placeholder', 'twmp-ath'); ?>" value="<?php echo esc_attr(get_search_query()); ?>" name="s" title="<?php echo _x('Search for:', 'label', 'twmp-ath'); ?>">
    </label>
    <?php
    printf(
        '<button class="search-submit" aria-label="%1$s">%2$s</button>',
        esc_attr(_x('Search', 'submit button', 'twmp-ath')),
        twmp_get_svg_icon('search') // phpcs:ignore -- Escaping not necessary here.
    );
    ?>
</form>