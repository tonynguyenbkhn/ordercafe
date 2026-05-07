<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hero Banner section template
 * Expects ACF fields (either via get_sub_field() when used inside a flexible content loop,
 * or get_field() when used directly):
 * - image (image ID, return_format = id)
 * - title (text)
 * - description (textarea)
 * - button_text (text)
 * - button_link (url)
 */

// Helper to prefer sub field when inside flexible content
function _hb_get($name) {
    if (function_exists('get_sub_field') && get_sub_field($name) !== null) {
        return get_sub_field($name);
    }
    if (function_exists('get_field')) {
        return get_field($name);
    }
    return null;
}

$image_id = _hb_get('image');
$section_id = _hb_get('section_id');
$title = _hb_get('title');
$description = _hb_get('description');
$button_text = _hb_get('button_text');
$button_link = _hb_get('button_link');

// Image attributes
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : '';
$image_srcset = $image_id ? wp_get_attachment_image_srcset($image_id, 'full') : '';
$image_sizes = $image_id ? wp_get_attachment_image_sizes($image_id, 'full') : '';
$image_alt = '';
if ($image_id) {
    $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
    $image_alt = $alt ? $alt : ($title ? $title : '');
}
?>

<?php if ($image_url || $title || $description || $button_text): ?>
<section class="hero-banner" role="banner"<?php echo $section_id ? ' id="' . esc_attr($section_id) . '"' : ''; ?>>
  <div class="hero-banner__media">
    <?php if ($image_id): ?>
      <?php get_template_part('templates/components/images', null, [
        'id' => $image_id,
        'size' => 'full',
        'class' => 'hero-banner__img',
        'alt' => $image_alt,
        'loading' => 'eager',
        'sizes' => $image_sizes,
      ]); ?>
    <?php endif; ?>
  </div>
  <div class="hero-banner__overlay"></div>
  <div class="hero-banner__content">
    <div class="container">
      <div class="hero-banner__grid">
        <div class="hero-banner__left">
          <?php if ($description): ?><p class="hero-banner__desc typo-text-lg-regular text-system-content-2"><?php echo nl2br(esc_html($description)); ?></p><?php endif; ?>
          <?php if ($button_text && $button_link): ?>
            <p class="hero-banner__cta">
              <?php get_template_part('templates/components/button', null, [
                'class' => 'bg-primary-500 text-system-white typo-system-button button-medium',
                'button_text' => $button_text,
                'button_url' => $button_link,
                'button_link_target' => '_self',
              ]); ?>
            </p>
          <?php endif; ?>
        </div>
        <div class="hero-banner__right">
          <?php if ($title): ?><h1 class="hero-banner__title typo-display-xl-regular text-system-white"><?php echo esc_html($title); ?></h1><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
