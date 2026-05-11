<?php
defined( 'ABSPATH' ) || exit;

// We need to wrap the variations with the 'variations' class for WooCommerce variations javascript.
?>
<div class="variations">
    <?php
    foreach ( $variation_attributes as $attribute => $attribute_options ) {
        ?>
        <label class="screen-reader-text" for="<?php echo esc_attr( sanitize_title( $attribute ) ); ?>"><?php echo esc_html( wc_attribute_label( $attribute ) ); ?></label>
        <?php
        wc_dropdown_variation_attribute_options( [
            'show_option_none' => wc_attribute_label( $attribute, $product ),
            'options'          => $attribute_options,
            'attribute'        => $attribute,
            'product'          => $product,
            ]
        );
    }
    ?>
    <input type="hidden" name="variation_id" class="variation_id" value="" />
</div>
<div class="variations-data"></div>
