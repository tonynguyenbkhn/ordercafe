<div class="widget-category">
    <h3 class="wp-block-heading"><?php echo esc_html__('Danh mục', 'taiwebmienphi-plus'); ?></h3>
    <ul class="wp-block-categories-list wp-block-danh-muc">
        <?php
        $twmp_terms = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
        ]);

        if (!is_wp_error($twmp_terms) && !empty($twmp_terms)) {
            foreach ($twmp_terms as $twmp_term) {
                $twmp_term_link = get_term_link($twmp_term);
                if (is_wp_error($twmp_term_link)) continue;

                $twmp_current_class = (is_tax('danh-muc', $twmp_term->slug)) ? ' current-cat' : '';

                echo '<li class="cat-item cat-item-' . esc_attr($twmp_term->term_id) . esc_attr($twmp_current_class) . '">';
                echo '<a href="' . esc_url($twmp_term_link) . '">' . esc_html($twmp_term->name) . '</a> ';
                echo '(' . esc_html($twmp_term->count) . ')';
                echo '</li>';
            }
        }
        ?>
    </ul>
</div>