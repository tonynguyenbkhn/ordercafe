<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args($args, [
    'next_post' => get_next_post(),
	'previous_post' => get_previous_post()
]);

$next_post = $data['next_post'];
$previous_post = $data['previous_post'];

if ($next_post || $previous_post) : ?>
    <div class="prev-next-post nav-links">
        <?php if ($previous_post) : ?>
            <div class="nav-item nav-previous">
                <a href="<?php echo get_permalink($previous_post); ?>" rel="prev">
                    <div class="nav-thumbnail">
                        <?php echo get_the_post_thumbnail($previous_post, 'thumbnail', ['class' => 'nav-image']); ?>
                    </div>
                    <div class="nav-content">
                        <span class="meta-nav" aria-hidden="true"><?php _e('Previous', 'twmp-ath'); ?></span>
                        <span class="screen-reader-text"><?php _e('Previous post:', 'twmp-ath'); ?></span>
                        <span class="post-title"><?php echo get_the_title($previous_post); ?></span>
                    </div>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($next_post) : ?>
            <div class="nav-item nav-next">
                <a href="<?php echo get_permalink($next_post); ?>" rel="next">
                    <div class="nav-thumbnail">
                        <?php echo get_the_post_thumbnail($next_post, 'thumbnail', ['class' => 'nav-image']); ?>
                    </div>
                    <div class="nav-content">
                        <span class="meta-nav" aria-hidden="true"><?php _e('Next', 'twmp-ath'); ?></span>
                        <span class="screen-reader-text"><?php _e('Next post:', 'twmp-ath'); ?></span>
                        <span class="post-title"><?php echo get_the_title($next_post); ?></span>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>