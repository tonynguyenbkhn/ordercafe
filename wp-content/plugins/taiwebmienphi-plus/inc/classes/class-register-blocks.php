<?php

namespace TWMP_PLUS\Inc;

use TWMP_PLUS\Inc\Traits\Singleton;

class REGISTER_BLOCKS
{
  use Singleton;

  protected function __construct()
  {
    $this->setup_hooks();
  }

  protected function setup_hooks()
  {
    add_action('init', array($this, 'twmp_plus_register_blocks'));
    add_filter('block_categories_all', array($this, 'twmp_plus_add_custom_block_category'));
    add_action('enqueue_block_editor_assets', array($this, 'twmp_register_assets'));
    // add_action('init', [$this, 'twmp_register_opengraph_meta']);
    // add_filter('document_title_parts', [$this, 'twmp_custom_wp_title'], 10);
    // add_action('wp_head', [$this, 'twmp_add_opengraph_meta_to_head'], 1);
  }

  public function twmp_plus_register_blocks()
  {
    $blocks = [
      ['name' => 'widget-recent-posts', 'options' => [
        'render_callback' => [$this, 'twmp_render_widget_recent_posts']
      ]],
      ['name' => 'widget-related-posts', 'options' => [
        'render_callback' => [$this, 'twmp_render_widget_related_posts']
      ]],
      ['name' => 'widget-platform', 'options' => [
        'render_callback' => [$this, 'twmp_render_widget_platform']
      ]],
      ['name' => 'widget-category', 'options' => [
        'render_callback' => [$this, 'twmp_render_widget_category']
      ]],
      ['name' => 'widget-product-detail', 'options' => [
        'render_callback' => [$this, 'twmp_render_widget_product_detail']
      ]],
      ['name' => 'widget-danh-muc', 'options' => [
        'render_callback' => [$this, 'twmp_render_widget_danh_muc']
      ]]
    ];

    foreach ($blocks as $block) {
      register_block_type(
        TWMP_PLUS_PLUGIN_DIR . 'build/blocks/' . $block['name'],
        isset($block['options']) ? $block['options'] : []
      );
    }
  }

  public function twmp_register_assets()
  {
    $editorAsset = include(TWMP_PLUS_PLUGIN_DIR . 'build/block-editor/index.asset.php');

    wp_register_script(
      'twmp_editor',
      plugins_url('build/block-editor/index.js', TWMP_PLUS_PLUGIN_FILE),
      $editorAsset['dependencies'],
      $editorAsset['version'],
      true
    );

    wp_enqueue_script('twmp_editor');
  }

  public function twmp_plus_add_custom_block_category($categories)
  {
    return array_merge(
      $categories,
      [
        [
          'slug'  => 'twmp-plus-category',
          'title' => 'TWMP Plus Blocks',
          'icon'  => 'wordpress'
        ],
      ]
    );
  }

  public function twmp_render_widget_recent_posts($atts)
  {
    ob_start();
    twmp_plus_get_template_part('recent-posts', null, $atts);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

  public function twmp_render_widget_related_posts($atts)
  {
    ob_start();
    twmp_plus_get_template_part('related-posts', null, $atts);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

  public function twmp_render_widget_platform($atts)
  {
    ob_start();
    twmp_plus_get_template_part('platform', null, $atts);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

  public function twmp_render_widget_category($atts)
  {
    ob_start();
    twmp_plus_get_template_part('category', null, $atts);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

  public function twmp_render_widget_danh_muc($atts)
  {
    ob_start();
    twmp_plus_get_template_part('danh-muc', null, $atts);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

  public function twmp_render_widget_product_detail($atts)
  {
    ob_start();
    twmp_plus_get_template_part('product-detail', null, $atts);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

  function twmp_register_opengraph_meta()
  {
    $meta_fields = [
      ['key' => 'meta_title', 'type' => 'string'],
      ['key' => 'meta_description', 'type' => 'string'],
      ['key' => 'meta_keyword', 'type' => 'string'],
      ['key' => 'og_title', 'type' => 'string'],
      ['key' => 'og_description', 'type' => 'string'],
      ['key' => 'og_image', 'type' => 'number'],
      ['key' => 'og_url', 'type' => 'string'],
      ['key' => 'og_type', 'type' => 'string'],
      ['key' => 'twitter_title', 'type' => 'string'],
      ['key' => 'twitter_description', 'type' => 'string'],
      ['key' => 'twitter_image', 'type' => 'string'],
      ['key' => 'twitter_image', 'type' => 'number'],
      ['key' => 'twitter_card', 'type' => 'string'],
      ['key' => 'canonical_url', 'type' => 'string'],
    ];
    foreach ($meta_fields as $field) {
      register_post_meta('', $field['key'], [
        'show_in_rest' => true,
        'single' => true,
        'type' => $field['type'],
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function () {
          return current_user_can('edit_posts');
        },
      ]);
    }
  }

  function twmp_custom_wp_title($title)
  {
    global $post;

    if (! $post) {
      return $title;
    }

    $post_id = $post->ID;
    if ((int) $post_id > 0) {
      $seo_title = get_post_meta($post_id, 'meta_title', true);
      if (! empty($seo_title)) {

        $title['title'] = $seo_title;
        return $title;
      }
    }

    return $title;
  }

  function twmp_add_opengraph_meta_to_head()
  {
    if (!is_singular()) {
      return;
    }

    global $post;
    if (!$post) return;

    $postId = $post->ID;
    $author_id = get_post_field('post_author', $postId);
    $username = get_the_author_meta('user_login', $author_id);
 
    $og_image = get_post_meta($postId, 'og_image', true);
    if (!$og_image) {
      $og_image = get_post_thumbnail_id($postId);
    }

    $twitter_image = get_post_meta($postId, 'twitter_image', true);
    if (!$twitter_image) {
      $twitter_image = get_post_thumbnail_id($postId);
    }
?>
    <meta name="description" content="<?php echo esc_attr(get_post_meta($postId, 'meta_description', true)); ?>" />
    <link rel="canonical" href="<?php echo get_post_meta($postId, 'canonical_url', true) ? esc_url(get_post_meta($postId, 'canonical_url', true)) : esc_url(get_permalink($postId)); ?>" />
    <meta property="og:type" content="<?php echo esc_attr(get_post_meta($postId, 'og_type', true)); ?>" />
    <meta property="og:title" content="<?php echo esc_attr(get_post_meta($postId, 'og_title', true)); ?>" />
    <meta property="og:description" content="<?php echo esc_attr(get_post_meta($postId, 'og_description', true)); ?>" />
    <meta property="og:url" content="<?php echo get_post_meta($postId, 'og_url', true) ? esc_url(get_post_meta($postId, 'og_url', true)) : esc_url(get_permalink($postId)); ?>" />
    <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>" />
    <meta property="og:updated_time" content="<?php echo esc_attr(get_the_modified_time('c', $postId)); ?>" />
    <meta property="fb:admins" content="61563445115550" />
    <?php

    if ($og_image) {
      $og_image_data = wp_get_attachment_image_src($og_image, 'full');
      $og_image_url = $og_image_data[0] ?? '';
      $image_width = $og_image_data[1] ?? '';
      $image_height = $og_image_data[2] ?? '';
      $image_alt = get_post_meta($og_image, '_wp_attachment_image_alt', true);
      $image_type = get_post_mime_type($og_image_url);
      if ($og_image_url) {
        echo '<meta property="og:image" content="' . esc_url($og_image_url) . '" />' . "\n";
        echo '<meta property="og:image:secure_url" content="' . esc_url($og_image_url) . '" />' . "\n";
        echo '<meta property="og:image:width" content="' . esc_attr($image_width) . '" />' . "\n";
        echo '<meta property="og:image:height" content="' . esc_attr($image_height) . '" />' . "\n";
        echo '<meta property="og:image:alt" content="' . esc_attr($image_alt) . '" />' . "\n";
        echo '<meta property="og:image:type" content="' . esc_attr($image_type) . '" />' . "\n";
      }
    }

    ?>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo esc_attr(get_post_meta($postId, 'twitter_title', true)); ?>" />
    <meta name="twitter:description" content="<?php echo esc_attr(get_post_meta($postId, 'twitter_description', true)); ?>" />
    <meta name="twitter:site" content="" />
    <meta name="twitter:creator" content="" />
    <?php
    if ($twitter_image) {
      $twitter_image_data = wp_get_attachment_image_src($twitter_image, 'full');
      $twitter_image_url = $twitter_image_data[0] ?? '';
      if ($twitter_image_url) {
        echo '<meta name="twitter:image" content="' . esc_url($twitter_image_url) . '" />' . "\n";
      }
    }

    ?>
    <meta name="twitter:image" content="" />
    <meta name="twitter:label1" content="Written by" />
    <meta name="twitter:data1" content="<?php echo esc_attr($username); ?>" />
    <meta name="twitter:label2" content="Time to read" />
    <meta name="twitter:data2" content="1 minute" />
<?php
  }

}
