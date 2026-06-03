<?php

$products = wc_get_products([
    'limit'  => -1,
    'return' => 'ids'
]);

$taxonomies = ['pa_da', 'pa_duong', 'pa_sua'];

foreach ($products as $product_id) {

    $product = wc_get_product($product_id);
    $attributes = $product->get_attributes();

    foreach ($taxonomies as $taxonomy) {

        // Bỏ qua nếu sản phẩm đã có attribute này
        if (isset($attributes[$taxonomy])) {
            continue;
        }

        $term_ids = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'fields'     => 'ids',
        ]);

        if (empty($term_ids)) {
            continue;
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
        $attribute->set_name($taxonomy);
        $attribute->set_options($term_ids);
        $attribute->set_position(count($attributes));
        $attribute->set_visible(true);
        $attribute->set_variation(false);

        $attributes[$taxonomy] = $attribute;
    }

    $product->set_attributes($attributes);
    $product->save();
}

echo "Done\n";