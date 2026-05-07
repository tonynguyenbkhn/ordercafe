<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search results are contained within a div.relevanssi-live-search-results
 * which you can style accordingly as you would any other element on your site.
 *
 * Some base styles are output in wp_footer that do nothing but position the
 * results container and apply a default transition, you can disable that by
 * adding the following to your theme's functions.php:
 *
 * add_filter( 'relevanssi_live_search_base_styles', '__return_false' );
 *
 * There is a separate stylesheet that is also enqueued that applies the default
 * results theme (the visual styles) but you can disable that too by adding
 * the following to your theme's functions.php:
 *
 * wp_dequeue_style( 'relevanssi-live-search' );
 *
 * You can use ~/relevanssi-live-search/assets/styles/style.css as a guide to customize
 *
 * @package Relevanssi Live Ajax Search
 */

?>

<?php if (have_posts()) : ?>
    <?php
    $status_element = '<div class="relevanssi-live-search-result-status" role="status" aria-live="polite"><p>';
    // Translators: %s is the number of results found.
    $status_element .= sprintf(esc_html(_n('%d result found.', '%d results found.', $wp_query->found_posts, 'twmp-ath')), intval($wp_query->found_posts));
    if ($wp_query->found_posts > 7) {
        $status_element .= ' ' . sprintf(esc_html(__('Press enter to see all the results.', 'twmp-ath')));
    }
    $status_element .= '</p></div>';

    /**
     * Filters the status element location.
     *
     * @param string The location. Possible values are 'before' and 'after'. If
     * the value is 'before', the status element will be added before the
     * results container. If the value is 'after', the status element will be
     * added after the results container. Default is 'before'. Any other value
     * will make the status element disappear.
     */
    $status_location = apply_filters('relevanssi_live_search_status_location', 'before');

    if (! in_array($status_location, array('before', 'after'), true)) {
        // No status element is displayed. Still add one for screen readers.
        $status_location = 'before';
        $status_element  = '<p class="screen-reader-text" role="status" aria-live="polite">';
        // Translators: %s is the number of results found.
        $status_element .= sprintf(esc_html(_n('%d result found.', '%d results found.', $wp_query->found_posts, 'twmp-ath')), intval($wp_query->found_posts));
        $status_element .= '</p>';
    }

    if ('before' === $status_location) {
        // Already escaped.
        echo wp_kses_post($status_element); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    while (have_posts()) :
        the_post();
    ?>
        <?php
        $product = wc_get_product(get_the_ID());
        $product_id = $product->get_id();

        $badges = function_exists('get_field') ? get_field('ath_badges', $product_id) : [];
        $location_detail = function_exists('get_field') ? (string) get_field('ath_location_detail', $product_id) : '';
        $location = function_exists('get_field') ? (string) get_field('ath_location', $product_id) : '';
        $start_datetime = function_exists('get_field') ? (string) get_field('ath_start_datetime', $product_id) : '';
        ?>

        <div class="relevanssi-live-search-result" role="option" aria-selected="false">

            <a class="live-search-item" href="<?php echo esc_url(get_permalink()); ?>">

                <div class="live-search-thumb">
                    <?php echo get_the_post_thumbnail(get_the_ID(), 'thumbnail'); ?>
                </div>
                <div class="live-search-content">

                    <?php
                    if (!empty($badges) && is_array($badges)) {
                        echo '<div class="product-badges">';

                        foreach ($badges as $badge) {
                            $text  = $badge['text'] ?? '';
                            $style = $badge['style'] ?? 'orange';

                            if ($text) {
                                printf(
                                    '<span class="ath-badge ath-badge--%s">%s</span>',
                                    esc_attr($style),
                                    esc_html($text)
                                );
                            }
                        }

                        echo '</div>';
                    }
                    ?>

                    <div class="live-search-title type-text-lg-medium text-system-black">
                        <?php the_title(); ?>
                    </div>

                    <div class="product-meta"><?php echo esc_html($start_datetime) . ',' . esc_html($location_detail); ?></div>
                </div>
            </a>

        </div>
    <?php
    endwhile;

    if ('after' === $status_location) {
        // Already escaped.
        echo wp_kses_post($status_element); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    ?>
<?php else : ?>
    <p class="relevanssi-live-search-no-results" role="status">
        <?php esc_html_e('No results found.', 'twmp-ath'); ?>
    </p>
    <?php
    if (function_exists('relevanssi_didyoumean')) {
        relevanssi_didyoumean(
            $wp_query->query_vars['s'],
            '<p class="relevanssi-live-search-didyoumean" role="status">'
                . __('Did you mean', 'twmp-ath') . ': ',
            '</p>'
        );
    }
    ?>
<?php endif; ?>