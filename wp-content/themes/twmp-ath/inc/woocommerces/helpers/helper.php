<?php

if (!defined('ABSPATH')) {
    exit;
}

function twmp_get_product_query_by_type($attr)
{
  if (empty($attr)) {
    return new WP_Error(400, __FUNCTION__ . ': ' . __('No attribute was defined for query.', 'twmp-ath'));
  }

  switch ($attr):

    case 'normal':

      return array(
        'post_type' => 'product',
        'orderby'   => 'DESC',
      );

      break;

    case 'featured':

      return array(
        'post_type'   => 'product',
        'tax_query'   => array(
          'relation'  => 'AND',
          array(
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => 'featured',
          ),
        )
      );

      break;

    case 'on_sale':

      return array(
        'post_type'   => 'product',
        'meta_query'  => array(
          'relation'  => 'OR',
          array(
            'key'     => '_sale_price',
            'value'   => 0,
            'compare' => '>',
            'type'    => 'numeric'
          ),
          array(
            'key'     => '_min_variation_sale_price',
            'value'   => 0,
            'compare' => '>',
            'type'    => 'numeric'
          )
        )
      );

      break;

    case 'random':

      return array(
        'post_type' => 'product',
        'orderby'   => 'rand',
      );

      break;

    case 'top_rated':
      return array(
        'post_status'    => 'publish',
        'post_type'      => 'product',
        'meta_key'       => '_wc_average_rating',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
      );

      break;

    case 'total_sales':

      return array(
        'post_type'      => 'product',
        'meta_key'       => 'total_sales',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_query'     => WC()->query->get_meta_query(),
      );

      break;

    default:

      return array(
        'post_type' => 'product'
      );

  endswitch;
}

function twmp_get_price_discount_percentage($product, $display_type = 'percentage')
{
  if (empty($product)) {
    return;
  }

  $is_on_sale = $product->is_on_sale();
  $price_sale = $product->get_sale_price();
  $price = $product->get_regular_price();
  $is_simple_product = $product->is_type('simple');
  $is_variable_product = $product->is_type('variable');
  $is_external_product = $product->is_type('external');
  $sale_text = __('On Sale', 'twmp-ath');
  $final_price = '';
  $out_of_stock = wcs_is_product_out_of_stock($product);

  // Out of stock.
  if ($out_of_stock || !$is_on_sale) {
    return '';
  }

  if ($display_type !== 'percentage') {
    return $sale_text;
  }

  if ($is_simple_product || $is_external_product) {
    $final_price = (($price - $price_sale) / $price) * 100;
    $final_price = '-' . round($final_price) . '%';
  } elseif ($is_variable_product) {
    $price_sale =  $product->get_variation_sale_price('min', false);
    $price = $product->get_variation_regular_price('min', false);

    $final_price = (($price - $price_sale) / $price) * 100;
    $final_price = '-' . round($final_price) . '%';
  }

  if (!empty($final_price)) {
    return $final_price;
  } else {
    return $sale_text;
  }
}

function twmp_is_product_out_of_stock($product)
{
  if (! $product || ! is_object($product)) {
    return false;
  }

  $in_stock     = $product->is_in_stock();
  $manage_stock = $product->managing_stock();
  $quantity     = $product->get_stock_quantity();

  if (
    ($product->is_type('simple') && (! $in_stock || ($manage_stock && 0 === $quantity))) ||
    ($product->is_type('variable') && $manage_stock && 0 === $quantity)
  ) {
    return true;
  }

  return false;
}

function twmp_load_location_data($key)
{
  $file = get_theme_file_path("inc/woocommerces/cities/{$key}.php");
  if (! file_exists($file)) {
    return array();
  }

  $data = require $file;
  if (! is_array($data)) {
    return array();
  }

  return $data;
}