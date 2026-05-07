<?php
$twmp_data = wp_parse_args(
    $args,
    array(
        'class'              => '',
        'post_id '           => '',
        'post_data'          => '',
        'post_title_limit'   => 25,
        'post_excerpt_limit' => 30,
        'view_more_button'   => __('View more', 'taiwebmienphi-plus'),
        'options' => [
            'show_excerpt' => true,
            'show_date' => true,
            'show_author' => true,
            'show_categories' => true,
            'show_view_more' => true
        ]
    )
);

$twmp_data['options']['show_excerpt'] = !!$twmp_data['showDescription'];
$twmp_data['options']['show_date'] = !!$twmp_data['showDate'];
$twmp_data['options']['show_author'] = !!$twmp_data['showAuthor'];
$twmp_data['options']['show_categories'] = !!$twmp_data['showCategory'];
$twmp_data['options']['show_view_more'] = !!$twmp_data['showViewMore'];

$twmp_class  = 'post-row';
$twmp_class .= ! empty($twmp_data['class']) ? esc_attr(' ' . $twmp_data['class']) : '';

$twmp_options = $twmp_data['options'];

$twmp_selected_posts = $twmp_data['selectedPostIds'];
$twmp_posts_per_page = !empty($twmp_data['postsPerPage'] ) && (int) $twmp_data['postsPerPage'] > 0 ? $twmp_data['postsPerPage'] : 5;

$twmp_args_resent_post = array(
    'post_status' => 'publish',
    'post_type' => 'post',
    'posts_per_page' => $twmp_posts_per_page
);

if (! empty($twmp_selected_posts)) {
    $twmp_args_resent_post['post__in'] = $twmp_selected_posts;
    $twmp_args_resent_post['orderby']  = 'post__in';
}

$twmp_data['post_title_limit'] = $twmp_data['titleLimit'] ? $twmp_data['titleLimit'] : $twmp_data['post_title_limit'];
$twmp_data['post_excerpt_limit'] = $twmp_data['excerptLimit'] ? $twmp_data['excerptLimit'] : $twmp_data['post_excerpt_limit'];
$twmp_data['view_more_button'] = $twmp_data['textViewMore'] ? $twmp_data['textViewMore'] : $twmp_data['post_excerpt_limit'];
$twmp_count = 0;
$twmp_query = new WP_Query($twmp_args_resent_post);
if ($twmp_query->have_posts()) :
    while ($twmp_query->have_posts()) : $twmp_query->the_post();
        $twmp_count++;
        $twmp_post_data = get_post(get_the_ID());
        $twmp_post_title       = ! empty($twmp_data['post_title_limit']) ? wp_trim_words($twmp_post_data->post_title, $twmp_data['post_title_limit'], '...') : $twmp_post_data->post_title;
        $twmp_post_description = $twmp_post_data->post_excerpt ? wp_trim_words($twmp_post_data->post_excerpt, $twmp_data['post_excerpt_limit'], '...') : wp_trim_words($twmp_post_data->post_content, $twmp_data['post_excerpt_limit'], '...');
        if ($twmp_count !== 1) {
?>
        <article class="<?php echo esc_attr($twmp_class); ?>">
            <div class="post-row__wrapper row">
                <?php /** */ ?>
                <div class="col-lg-4 col-md-5 col-sm-5 col-5">
                    <a class="post-row__overlay-link" href="<?php echo esc_url_raw(get_permalink($twmp_post_data)); ?>" title="">
                        <?php
                        get_template_part('templates/core-blocks/image', null, [
                            'image_id' => get_post_thumbnail_id($twmp_post_data),
                            'image_size' => 'full',
                            'lazyload' => false,
                            'class' => 'pe-none image--cover post-row__image',
                        ]);
                        ?>
                    </a>
                </div>
                <?php /** */ ?>
                <div class="col-lg-8 col-lg-7 col-sm-7 col-7">
                <!-- <div class="col-12"> -->
                    <div class="post-row__content">
                        <a class="post-row__title-link" href="<?php echo esc_url_raw(get_permalink($twmp_post_data)); ?>" title="">
                            <h3 class="post-row__title h6"><?php echo esc_html($twmp_post_title); ?></h3>
                        </a>
                        <?php if ($twmp_options['show_excerpt']): ?>
                            <p class="post-row__description"><?php echo esc_html($twmp_post_description); ?> </p>
                        <?php endif; ?>
                        <?php
                        get_template_part('templates/blocks/post-meta', null, [
                            'date' => $twmp_options['show_date'],
                            'author' => $twmp_options['show_author'],
                            'categories' => $twmp_options['show_categories'],
                            'class' => 'post-row__post-meta'
                        ]);
                        ?>
                        <?php if ($twmp_options['show_view_more'] && $twmp_data['view_more_button'] !== '') : ?>
                            <div class="post-row__footer">
                                <?php
                                get_template_part('templates/core-blocks/button', null, [
                                    'class'       => 'post-row__button rounded-0 text-white',
                                    'button_text' => $twmp_data['view_more_button'],
                                    'button_url' => esc_url_raw(get_permalink($twmp_post_data)),
                                    'type' => 'dark'
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
<?php } else { ?>
     <article class="<?php echo esc_attr($_class); ?>">
            <div class="post-row__wrapper row">
                <?php /** */ ?>
                <div class="col-12 mb-2">
                    <a class="post-row__overlay-link" href="<?php echo esc_url_raw(get_permalink($twmp_post_data)); ?>" title="">
                        <?php
                        get_template_part('templates/core-blocks/image', null, [
                            'image_id' => get_post_thumbnail_id($twmp_post_data),
                            'image_size' => 'full',
                            'lazyload' => false,
                            'class' => 'pe-none image--cover post-row__image',
                        ]);
                        ?>
                    </a>
                </div>
                <?php /** */ ?>
                <div class="col-12">
                    <div class="post-row__content">
                        <a class="post-row__title-link" href="<?php echo esc_url_raw(get_permalink($twmp_post_data)); ?>" title="">
                            <h3 class="post-row__title h6"><?php echo esc_html($twmp_post_title); ?></h3>
                        </a>
                        <?php if ($twmp_options['show_excerpt']): ?>
                            <p class="post-row__description"><?php echo esc_html($twmp_post_description); ?> </p>
                        <?php endif; ?>
                        <?php
                        get_template_part('templates/blocks/post-meta', null, [
                            'date' => $twmp_options['show_date'],
                            'author' => $twmp_options['show_author'],
                            'categories' => $twmp_options['show_categories'],
                            'class' => 'post-row__post-meta'
                        ]);
                        ?>
                        <?php if ($twmp_options['show_view_more'] && $twmp_data['view_more_button'] !== '') : ?>
                            <div class="post-row__footer">
                                <?php
                                get_template_part('templates/core-blocks/button', null, [
                                    'class'       => 'post-row__button rounded-0 text-white',
                                    'button_text' => $twmp_data['view_more_button'],
                                    'button_url' => esc_url_raw(get_permalink($twmp_post_data)),
                                    'type' => 'dark'
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
    <?php }
    endwhile;
    wp_reset_postdata();
endif;
