<?php

/**
 * WooCommerce Artists Template Helpers
 *
 * Modular functions for retrieving and rendering product artists data.
 * Includes proper ACF integration, data sanitization, and escaping.
 *
 * @package TWMP_ATH
 * @subpackage Inc/Helpers
 * @since 1.0.0
 */

// Prevent direct file access.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Get product artists from ACF field with sanitization.
 *
 * Retrieves artist data from the ACF repeater field and sanitizes all output.
 * This function handles the ACF integration and ensures data safety.
 *
 * @param int $product_id The product ID.
 * @return array Array of sanitized artist data, or empty array if none.
 * @since 1.0.0
 */

function twmp_get_product_artists($product_id = 0)
{
	// Validate product ID.
	if (empty($product_id)) {
		return array();
	}

	$product_id = absint($product_id);

	// Verify function exists (ACF requirement).
	if (! function_exists('get_field')) {
		return array();
	}

	// Get artists data from ACF field.
	$artists_data = get_field('field_ath_artists', $product_id);

	// Return empty array if no data.
	if (empty($artists_data) || ! is_array($artists_data)) {
		return array();
	}

	// Sanitize and prepare artist data.
	$artists = array();
	foreach ($artists_data as $artist_item) {
		// Handle artist data as either object/array or ID.
		$artist_data = twmp_get_artist_from_item($artist_item);

		if (empty($artist_data)) {
			continue;
		}

		// Sanitize each artist entry.
		$sanitized_artist = twmp_sanitize_artist_data($artist_data);

		// Only include artists with at least an ID or name.
		if (! empty($sanitized_artist['id']) || ! empty($sanitized_artist['name'])) {
			$artists[] = $sanitized_artist;
		}
	}

	return $artists;
}

/**
 * Get artist data from ACF field item.
 *
 * Handles both post objects and post IDs from ACF field.
 * ACF field can be configured to return either format.
 *
 * @param mixed $artist_item Artist data (ID, array, or object).
 * @return array Artist data array or empty if invalid.
 * @since 1.0.0
 */
function twmp_get_artist_from_item($artist_item) {
	// Handle numeric ID (post ID).
	if (is_numeric($artist_item)) {
		$post_id = absint($artist_item);
		$post = get_post($post_id);
		
		if (!$post || 'publish' !== $post->post_status) {
			return array();
		}

		// Build artist data from post.
		return array(
			'ID'          => $post->ID,
			'id'          => $post->ID,
			'name'        => $post->post_title,
			'position'    => get_post_meta($post->ID, '_artist_position', true) ?: '',
			'description' => $post->post_content,
			'url'         => get_post_meta($post->ID, '_artist_url', true) ?: '',
			'image'       => array(
				'id'  => get_post_thumbnail_id($post->ID),
				'url' => get_the_post_thumbnail_url($post->ID, 'full') ?: '',
				'alt' => get_post_meta(get_post_thumbnail_id($post->ID), '_wp_attachment_image_alt', true) ?: '',
			),
		);
	}

	// Handle array/object format (ACF post object).
	if (is_array($artist_item) || is_object($artist_item)) {
		return (array) $artist_item;
	}

	return array();
}

/**
 * Sanitize individual artist data.
 *
 * Ensures all artist fields are properly sanitized for safe output.
 * Handles images, text, and URL fields.
 *
 * @param array $artist Raw artist data.
 * @return array Sanitized artist data.
 * @since 1.0.0
 */
function twmp_sanitize_artist_data($artist)
{
	// Initialize sanitized array.
	$sanitized = array(
		'id'          => '',
		'image'       => array(),
		'name'        => '',
		'position'    => '',
		'description' => '',
		'url'         => '',
	);

	// Sanitize ID (typically numeric or post ID).
	if (! empty($artist['ID'])) {
		$sanitized['id'] = absint($artist['ID']);
	} elseif (! empty($artist['id'])) {
		$sanitized['id'] = absint($artist['id']);
	}

	// Sanitize image - handle array or image ID.
	if (! empty($artist['image'])) {
		$sanitized['image'] = twmp_sanitize_image_data($artist['image']);
	}

	// Sanitize name/title - plain text with limited tags.
	if (! empty($artist['name'])) {
		$sanitized['name'] = sanitize_text_field($artist['name']);
	}

	// Sanitize position - plain text only.
	if (! empty($artist['position'])) {
		$sanitized['position'] = sanitize_text_field($artist['position']);
	}

	// Sanitize description - allow basic HTML tags.
	// Support both 'description' and 'post_content' fields.
	$description = ! empty($artist['description']) ? $artist['description'] : (! empty($artist['post_content']) ? $artist['post_content'] : '');
	if (! empty($description)) {
		$allowed_tags = array(
			'p'      => array(),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'b'      => array(),
			'i'      => array(),
		);
		$sanitized['description'] = wp_kses($description, $allowed_tags);
	}

	// Sanitize URL - validate as proper URL.
	if (! empty($artist['url'])) {
		$sanitized['url'] = esc_url_raw($artist['url']);
	}

	return $sanitized;
}

/**
 * Sanitize image data from ACF.
 *
 * Handles different image field formats (image ID, array, or attachment ID).
 * Returns sanitized image data suitable for wp_get_attachment_image.
 *
 * @param mixed $image Image data from ACF.
 * @return array Sanitized image data.
 * @since 1.0.0
 */
function twmp_sanitize_image_data($image)
{
	$sanitized = array(
		'id'  => 0,
		'url' => '',
		'alt' => '',
	);

	// Handle array format (ACF image object).
	if (is_array($image)) {
		if (! empty($image['ID'])) {
			$sanitized['id'] = absint($image['ID']);
		} elseif (! empty($image['id'])) {
			$sanitized['id'] = absint($image['id']);
		}

		if (! empty($image['url'])) {
			$sanitized['url'] = esc_url_raw($image['url']);
		}

		if (! empty($image['alt'])) {
			$sanitized['alt'] = sanitize_text_field($image['alt']);
		}
	} elseif (is_numeric($image)) {
		// Handle image ID.
		$image_id = absint($image);
		if ($image_id > 0) {
			$sanitized['id']  = $image_id;
			$sanitized['url'] = wp_get_attachment_url($image_id);
			$sanitized['alt'] = get_post_meta($image_id, '_wp_attachment_image_alt', true);

			if (! empty($sanitized['alt'])) {
				$sanitized['alt'] = sanitize_text_field($sanitized['alt']);
			}
		}
	}

	return $sanitized;
}

/**
 * Render artists grid layout.
 *
 * Displays artists in a responsive grid with images, names, positions, and descriptions.
 * Applies proper escaping for all output.
 *
 * @param array $artists Array of sanitized artist data.
 * @since 1.0.0
 */
function twmp_render_artists_grid($artists)
{
	// Validate input.
	if (empty($artists) || ! is_array($artists)) {
		return;
	}

?>
	<div class="twmp-artists-section">
		<h2 class="twmp-artists-title"><?php esc_html_e('Artists', 'twmp-ath'); ?></h2>

		<div class="twmp-artists-grid">
			<?php foreach ($artists as $artist) : ?>
				<?php
				// Verify artist has required data.
				if (empty($artist['name']) && empty($artist['image']['id'])) {
					continue;
				}

				// Extract artist data with defaults.
				$artist_id   = ! empty($artist['id']) ? $artist['id'] : '';
				$image_id    = ! empty($artist['image']['id']) ? $artist['image']['id'] : 0;
				$image_url   = ! empty($artist['image']['url']) ? $artist['image']['url'] : '';
				$image_alt   = ! empty($artist['image']['alt']) ? $artist['image']['alt'] : '';
				$name        = ! empty($artist['name']) ? $artist['name'] : '';
				$position    = ! empty($artist['position']) ? $artist['position'] : '';
				$description = ! empty($artist['description']) ? $artist['description'] : '';
				$url         = ! empty($artist['url']) ? $artist['url'] : '';

				// Generate unique ID for accessibility.
				$artist_unique_id = 'artist-' . (! empty($artist_id) ? $artist_id : uniqid());
				?>
				<div class="twmp-artist-card" id="<?php echo esc_attr($artist_unique_id); ?>">
					<?php
					// Display artist image.
					if ($image_id > 0) {
						// Use WordPress native image function for optimization.
						echo wp_get_attachment_image($image_id, 'full', false, array('class' => 'twmp-artist-image'));
					} elseif (! empty($image_url)) {
						// Fallback to direct URL if image ID unavailable.
						printf(
							'<img src="%s" alt="%s" class="twmp-artist-image" loading="lazy" decoding="async" />',
							esc_url($image_url),
							esc_attr(! empty($image_alt) ? $image_alt : $name)
						);
					}
					?>

					<div class="twmp-artist-content">
						<?php if (! empty($name)) : ?>
							<h3 class="twmp-artist-name">
								<?php
								if (! empty($url)) {
									printf(
										'<a href="%s" title="%s">%s</a>',
										esc_url($url),
										esc_attr($name),
										esc_html($name)
									);
								} else {
									echo esc_html($name);
								}
								?>
							</h3>
						<?php endif; ?>

						<?php if (! empty($position)) : ?>
							<p class="twmp-artist-position">
								<?php echo esc_html($position); ?>
							</p>
						<?php endif; ?>

						<?php if (! empty($description)) : ?>
							<div class="twmp-artist-description">
								<?php
								// Description already sanitized in twmp_sanitize_artist_data.
								echo wp_kses_post($description);
								?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php
}

/**
 * Render fallback when no artists available.
 *
 * Displays a message when no artists are associated with the product.
 *
 * @since 1.0.0
 */
function twmp_render_no_artists_fallback()
{
?>
	<div class="twmp-artists-section twmp-artists-empty">
		<p class="twmp-no-artists-message">
			<?php esc_html_e('No artists available for this product.', 'twmp-ath'); ?>
		</p>
	</div>
<?php
}
