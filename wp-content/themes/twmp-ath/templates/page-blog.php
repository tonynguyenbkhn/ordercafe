<?php
/**
 * Template Name: Page Blog
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
get_template_part('templates/blocks/page-title', null, ['class' => 'single__page-title', 'show_breadcrumbs' => false]);
if ('page' === get_post_type()) :
    get_template_part('templates/blocks/post-meta', null, [
        'date' => true,
        'author' => true,
        'categories' => false,
        'class' => 'single__post-meta mt-0'
    ]);
endif;
get_template_part('templates/blocks/share-icon', null, ['class' => 'mt-1']);
?>

<div class="page-single-tin-tuc single-tin-tuc__post">
    <div class="container single-tin-tuc__container">
        <div class="row single-tin-tuc__row">
            <div class="single-tin-tuc__col single-tin-tuc__left col-lg-8 col-md-12 col-12">
                <main id="primary" class="site-main">

                    <?php
                    while (have_posts()) :
                        the_post();

                        get_template_part('template-parts/content', get_post_type());

                        // get_template_part('template-parts/singles/prev-next-post', null, [
                        // 	'next_post' => get_next_post(),
                        // 	'previous_post' => get_previous_post()
                        // ]);

                        get_template_part('templates/blocks/share-icon', null, []);

                    ?>

                        <?php // get_template_part('template-parts/singles/author-bio', null, []); 
                        ?>
                        <?php /*
						<div class="comment-list-wrap">
							<?php
							if (comments_open() || get_comments_number()) :
								comments_template();
							endif; ?>
						</div>
						*/ ?>
                    <?php endwhile; // End of the loop.

                    $categories = wp_get_post_categories($post->ID);

                    if (!empty($categories)) {
                        $args = array(
                            'category__in'   => $categories, // bài viết thuộc các category này
                            'post__not_in'   => array($post->ID), // loại trừ bài viết hiện tại
                            'posts_per_page' => 4, // số lượng bài viết liên quan
                            'orderby'        => 'date',
                            'order'          => 'DESC',
                        );

                        $related_posts = new WP_Query($args); ?>

                        <?php
                        if ($related_posts->have_posts()) :
                        ?>
                            <div class="related-posts">
                                <?php
                                get_template_part('templates/core-blocks/heading', null, [
                                    'title_class' => 'related-posts__title mb-0',
                                    'description_class' => '',
                                    'class' => 'related-posts__header',
                                    'title' => esc_html__('Related Articles', 'twmp-ath'),
                                    'description' => '',
                                ]); ?>
                                <?php
                                echo '<div class="row">';
                                while ($related_posts->have_posts()) :
                                    $related_posts->the_post();
                                    get_template_part('templates/blocks/post-row', null, [
                                        'class' => '',
                                        'post_data' => get_post(get_the_ID()),
                                        'view_more_button' => esc_html__('', 'twmp-ath'),
                                        'post_excerpt_limit' => 30,
                                        'options' => [
                                            'show_excerpt' => true,
                                            'show_date' => true,
                                            'show_author' => false,
                                            'show_categories' => true
                                        ]
                                    ]);
                                endwhile;
                                echo '</div>';
                                wp_reset_postdata(); ?>
                            </div>
                    <?php
                        endif;
                    }
                    ?>

                </main><!-- #main -->
            </div>
            <div class="single-tin-tuc__col single-tin-tuc__right col-lg-4 col-md-12 col-12">
                <?php get_sidebar(); ?>
            </div>
        </div>
    </div>
</div>
<?php

get_footer();
