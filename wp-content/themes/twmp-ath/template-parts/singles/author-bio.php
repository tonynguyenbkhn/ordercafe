<?php

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_the_author_meta('ID');
$author_name = get_the_author();
$author_desc = get_the_author_meta('description');
$author_avatar = get_avatar($author_id, 100);
$author_posts_url = get_author_posts_url($author_id);
$author_post_count = count_user_posts($author_id);
?>

<div class="author-bio">
    <div class="author-avatar">
        <?php echo wp_kses_post( $author_avatar ); ?>
    </div>
    <div class="author-info">
        <h3 class="author-name"><a href="<?php echo esc_url($author_posts_url); ?>"><?php echo esc_html($author_name); ?></a></h3>
        <p class="author-description"><?php echo esc_html($author_desc); ?></p>
        <p class="author-post-count"><?php echo sprintf(__('Published %d posts', 'twmp-ath'), $author_post_count); ?></p>
    </div>
</div>