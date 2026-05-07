<?php

function twmp_theme_is_localhost()
{
	$theme_header = filter_input(INPUT_SERVER, 'HTTP_X_TWMP_THEME_HEADER', FILTER_UNSAFE_RAW);

	return is_string($theme_header) && 'development' === sanitize_text_field($theme_header);
}

function twmp_format_css_variables($css)
{
	// Remove unused css
	$css = preg_replace('/@custom-media --(.*)\;/i', '', $css);

	// Remove empty whitespace
	// $css = preg_replace('/\s+/', '', $css);

	return $css;
}


if (!function_exists('twmp_format_comment')) {
	function twmp_format_comment($comment, $args, $depth)
	{
		if ('div' === $args['style']) {
			$tag = 'div';
			$add_below = 'comment';
		} else {
			$tag = 'li';
			$add_below = 'div-comment';
		} ?>

		<<?php echo esc_html($tag) . ' '; ?><?php comment_class(empty($args['has_children']) ? '' : 'parent'); ?> id="comment-<?php comment_ID() ?>">

			<?php
			switch ($comment->comment_type):
				case 'pingback':
				case 'trackback': ?>
					<div class="pingback-entry"><span
							class="pingback-heading"><?php esc_html_e('Pingback:', 'twmp-ath'); ?></span> <?php comment_author_link(); ?>
					</div>
					<?php
					break;
				default:

					if ('div' != $args['style']) { ?>
						<div id="div-comment-<?php comment_ID() ?>" class="comment-body w-100">
						<?php } ?>
						<div class="comment-author vcard w-100 d-flex">
							<?php
							if ($args['avatar_size'] != 0) {
								$avatar_size = !empty($args['avatar_size']) ? $args['avatar_size'] : 70;
								echo get_avatar($comment, $avatar_size);
							}
							?>
							<div class="comment-details">
								<div class="top">
									<?php printf(__('<cite class="fn">%s</cite> <span class="says">says:</span>', 'twmp-ath'), get_comment_author_link()); ?>
									<a class="date-time"
										href="<?php echo htmlspecialchars(get_comment_link($comment->comment_ID)); ?>"><?php
																														printf(
																															__('%1$s', 'twmp-ath'),
																															get_comment_date()
																														); ?>
									</a>
									<span class="separator">-</span>
									<div class="reply"><?php
														comment_reply_link(array_merge($args, array(
															'add_below' => $add_below,
															'depth' => $depth,
															'max_depth' => $args['max_depth']
														))); ?>
									</div>
								</div>
								<div class="comment-text"><?php comment_text(); ?></div>
								<?php
								if ($comment->comment_approved == '0') { ?>
									<em class="comment-awaiting-moderation"><?php _e('Your comment is awaiting moderation.', 'twmp-ath'); ?></em>
									<br /><?php
										} ?>
							</div>
							<?php
							if ('div' != $args['style']) { ?>
						</div>
	<?php }
							break;
					endswitch;
				}
			}

			function twmp_breadcrumbs($args = array())
			{

				$breadcrumb = apply_filters('Twmp_Breadcrumb_object', null, $args);

				if (!is_object($breadcrumb))
					$breadcrumb = new \TWMP_THEME\Inc\Breadcrumbs($args);

				return $breadcrumb->trail();
			}

			function twmp_time_elapsed_string($datetime, $full = false)
			{
				$now = new DateTime;
				$ago = new DateTime($datetime);
				$diff = $now->diff($ago);

				$weeks = floor($diff->d / 7);
				$days = $diff->d - $weeks * 7;

				$string = [
					'y' => $diff->y,
					'm' => $diff->m,
					'w' => $weeks,
					'd' => $days,
					'h' => $diff->h,
					'i' => $diff->i,
					's' => $diff->s,
				];

				$labels = [
					'y' => 'năm',
					'm' => 'tháng',
					'w' => 'tuần',
					'd' => 'ngày',
					'h' => 'giờ',
					'i' => 'phút',
					's' => 'giây',
				];

				foreach ($string as $k => $v) {
					if ($v) {
						$string[$k] = $v . ' ' . $labels[$k];
					} else {
						unset($string[$k]);
					}
				}

				if (!$full) {
					$string = array_slice($string, 0, 1);
				}

				return $string ? implode(', ', $string) . ' trước' : 'vừa xong';
			}
