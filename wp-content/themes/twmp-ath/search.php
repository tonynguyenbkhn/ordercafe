<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
get_template_part('templates/blocks/page-title', null, ['class' => 'reverse-title','text'=> esc_attr__('Blog', 'twmp-ath')]);

$paged = (get_query_var('paged')) ? (int)get_query_var('paged') : 1;
$post_args = [
	'post_type'      => 'post',
	'posts_per_page' => get_option('posts_per_page') ? get_option('posts_per_page') : 12,
	'paged'          => $paged,
    's'              => get_search_query()
];
$query = twmp_get_post_query($post_args);
$total = $query->max_num_pages;

?>
<div class="page-blog">
	<div class="container page-blog__container">
		<div class="row page-blog__row">
		<div class="page-blog__col page-blog__left col-lg-8 col-md-12 col-12">
				<main id="primary" class="site-main">

					<?php if ($query->have_posts()) : ?>
					<?php
						echo '<div class="row">';
						while ($query->have_posts()) :
							$query->the_post();

							// get_template_part('templates/blocks/post-card', null, [
							// 	'class' => 'col-lg-6 post-card--col',
							// 	'post_data' => get_post(get_the_ID()),
							// 	'post_id' => get_the_ID(),
							// 	'view_more_button' => esc_html__('Read More...', 'twmp-ath'),
							// ]);

							get_template_part('templates/blocks/post-row', null, [
								'class' => '',
								'post_data' => get_post(get_the_ID()),
								'view_more_button' => esc_html__('Read More...', 'twmp-ath'),
								'options' => [
									'show_excerpt' => true,
									'show_date' => true,
									'show_author' => true,
									'show_categories' => false
								]
							]);

						endwhile;
						echo '</div>';
						if ($total > 1) {
							$args = array(
								'total' => $total,
								'current' => $paged,
							);
							echo '<div class="pagination">';
							twmp_component_pagi_post($args);
							echo '</div>';
						}
						wp_reset_postdata();
					else :
						get_template_part('template-parts/content', 'none');
					endif;
					?>

				</main><!-- #main -->
			</div>
			<div class="page-blog__col page-blog__right col-lg-4 col-md-12 col-12">
				<?php get_sidebar(); ?>
			</div>
		</div>
	</div>
</div>
<?php

get_footer();
