<?php
function twmp_allow_svg_upload($mimes)
{
    // Chỉ cho phép Super Admin hoặc Admin thường trong site đơn
    if (function_exists('is_multisite') && is_multisite()) {
        if (current_user_can('manage_network') || current_user_can('administrator')) {
            $mimes['svg'] = 'image/svg+xml';
        }
    } else {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
}
add_filter('upload_mimes', 'twmp_allow_svg_upload');

function twmp_shortcode_current_year()
{
    return gmdate('Y');
}
add_shortcode('year', 'twmp_shortcode_current_year');

function twmp_get_sanitized_query_text($key)
{
    $value = filter_input(INPUT_GET, $key, FILTER_DEFAULT);

    if (! is_string($value) || '' === $value) {
        return '';
    }

    return sanitize_text_field($value);
}

function twmp_orderby_product_shortcode()
{
    // Lấy query hiện tại
    $current_query = array();

    $key_name = twmp_get_sanitized_query_text('key_name');

    if ('' !== $key_name) {
        $current_query['key_name'] = $key_name;
    }

    // Các giá trị hiện tại
    $orderby = isset($current_query['orderby']) ? $current_query['orderby'] : '';
    $orderby_view = isset($current_query['orderby_view']) ? $current_query['orderby_view'] : '';
    $orderby_promotion = isset($current_query['orderby_promotion']) ? $current_query['orderby_promotion'] : '';

    // Class active
    $xemnhieu_class = ($orderby_view === 'product_views') ? 'active' : '';
    $khuyenmai_class = ($orderby_promotion === 'promotion') ? 'active' : '';
    $price_box_class = ($orderby === 'price' || $orderby === 'price-desc') ? 'active' : '';

    // Hàm build lại query string
    function twmp_build_query_url($override = [], $remove_keys = [])
    {
        $query = array();

        $key_name = twmp_get_sanitized_query_text('key_name');

        if ('' !== $key_name) {
            $query['key_name'] = $key_name;
        }

        // Gộp override
        foreach ($override as $key => $val) {
            $query[$key] = $val;
        }

        // Xoá những key cần remove
        foreach ($remove_keys as $key) {
            unset($query[$key]);
        }

        // Xoá key rỗng
        foreach ($query as $key => $val) {
            if ($val === '') unset($query[$key]);
        }

        return count($query) ? '?' . http_build_query($query) : '?';
    }

    ob_start();
?>
    <div class="orderby-product">
        <div class="orderby">
            <?php if ($xemnhieu_class === 'active'): ?>
                <a href="<?php echo esc_url(twmp_build_query_url([], ['orderby_view'])); ?>" class="active"><?php echo esc_html__('View more', 'taiwebmienphi-plus'); ?></a>
            <?php else: ?>
                <a href="<?php echo esc_url(twmp_build_query_url(['orderby_view' => 'product_views'])); ?>"><?php echo esc_html__('View more', 'taiwebmienphi-plus'); ?></a>
            <?php endif; ?>

            <?php if ($khuyenmai_class === 'active'): ?>
                <a href="<?php echo esc_url(twmp_build_query_url([], ['orderby_promotion'])); ?>" class="active"><?php echo esc_html__('Promotion', 'taiwebmienphi-plus'); ?></a>
            <?php else: ?>
                <a href="<?php echo esc_url(twmp_build_query_url(['orderby_promotion' => 'promotion'])); ?>"><?php echo esc_html__('Promotion', 'taiwebmienphi-plus'); ?></a>
            <?php endif; ?>

            <span class="orderbys_price <?php echo esc_attr($price_box_class); ?>">
                <span class="text"><?php echo esc_html__('Price', 'taiwebmienphi-plus'); ?> <?php echo twmp_get_svg_icon('caret-down'); ?></span>
                <span class="price">
                    <span class="content">
                        <b><?php echo esc_html__('Price', 'taiwebmienphi-plus'); ?></b>
                        <span class="nav-list">
                            <?php if ($orderby === 'price'): ?>
                                <a class="up active"
                                    href="<?php echo esc_url(twmp_build_query_url([], ['orderby'])); ?>"
                                    title="<?php echo esc_attr__('Low to high', 'taiwebmienphi-plus'); ?>"><?php echo esc_html__('Low to high', 'taiwebmienphi-plus'); ?></a>
                            <?php else: ?>
                                <a class="up"
                                    href="<?php echo esc_url(twmp_build_query_url(['orderby' => 'price'])); ?>"
                                    title="<?php echo esc_attr__('Low to high', 'taiwebmienphi-plus'); ?>"><?php echo esc_html__('Low to high', 'taiwebmienphi-plus'); ?></a>
                            <?php endif; ?>

                            <!-- Giá cao đến thấp -->
                            <?php if ($orderby === 'price-desc'): ?>
                                <a class="down active"
                                    href="<?php echo esc_url(twmp_build_query_url([], ['orderby'])); ?>"
                                    title="<?php echo esc_attr__('Price high to low', 'taiwebmienphi-plus'); ?>"><?php echo esc_html__('High to low', 'taiwebmienphi-plus'); ?></a>
                            <?php else: ?>
                                <a class="down"
                                    href="<?php echo esc_url(twmp_build_query_url(['orderby' => 'price-desc'])); ?>"
                                    title="<?php echo esc_attr__('Price high to low', 'taiwebmienphi-plus'); ?>"><?php echo esc_html__('High to low', 'taiwebmienphi-plus'); ?></a>
                            <?php endif; ?>
                        </span>
                    </span>
                </span>
            </span>

        </div>
    </div>

<?php
    return ob_get_clean();
}
add_shortcode('orderby_product', 'twmp_orderby_product_shortcode');

function twmp_filter_products_shortcode()
{
    $_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes();
    $taxonomy           = 'pa_color';
    $query_type         = 'or';
    $display_type       = 'list';

    if (! taxonomy_exists($taxonomy)) {
        return;
    }

    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
    ));

    if (0 === count($terms)) {
        return;
    }

    $widget = new TWMP_Layered_Nav_Widget();

    ob_start();
?>
    <div class="filter-products">
        <div class="filterModule">
            <div class="scrollbar">
                <div class="filter-sort__list-filter">
                    <div class="button__filter-parent"><span><?php echo esc_html__('Filter', 'taiwebmienphi-plus') ?><?php echo twmp_get_svg_icon('filter-product') ?></span></div>
                    <div class="widget woocommerce widget_layered_nav woocommerce-widget-layered-nav  "><span class="isures-wd--title count"><span class="name-title-w "><?php echo esc_html__('Color', 'taiwebmienphi-plus') ?></span> <?php echo twmp_get_svg_icon('caret-down') ?></span>
                        <div class="list-group-attribute">
                            <h3><?php echo esc_html__('Color', 'taiwebmienphi-plus') ?></h3>
                            <?php $widget->public_layered_nav_list($terms, $taxonomy, $query_type); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="productssss-total">
                <div class="content-attribute">
                    <div class="widget woocommerce widget_layered_nav woocommerce-widget-layered-nav  "><span class="isures-wd--title count"><span class="name-title-w "><?php echo esc_html__('Color', 'taiwebmienphi-plus') ?></span> <?php echo twmp_get_svg_icon('caret-down') ?></span>
                        <div class="list-group-attribute">
                            <h3><?php echo esc_html__('Color', 'taiwebmienphi-plus') ?></h3>
                            <?php $widget->public_layered_nav_list($terms, $taxonomy, $query_type); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="products-attribute">
                <div class="list-group-attribute">
                    <h3><?php echo esc_html__('Color', 'taiwebmienphi-plus') ?></h3>
                    <?php $widget->public_layered_nav_list($terms, $taxonomy, $query_type); ?>
                </div>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('filter_products', 'twmp_filter_products_shortcode');


function twmp_icon_shortcode($atts)
{
    $atts = shortcode_atts([
        'name' => '',
    ], $atts, 'twmp_icon');

    return twmp_get_svg_icon($atts['name']);
}
add_shortcode('twmp_icon', 'twmp_icon_shortcode');

add_shortcode('order-tracking', function () {
    ob_start();
    $block_attributes = array(
        'endpoint' => 'search_order_by_phone'
    );
?>
    <div class="order-lookup-wrapper" data-settings='<?php echo json_encode($block_attributes) ?>' data-block="order-tracking">
        <div class="order-lookup">
            <h3 class="title-lookup"><?php echo esc_html__('To check the status of your order, please enter your phone number in the box below and press the “Check” button.', 'taiwebmienphi-plus'); ?></h3>
            <div class="form-warranty-lookup">
                <form class="form">
                    <div class="input"><input type="text" class="search-phone" placeholder="<?php echo esc_attr__('Enter phone number', 'taiwebmienphi-plus'); ?>" required></div>
                    <div class="button"><button type="submit"><?php echo esc_html__('Check', 'taiwebmienphi-plus'); ?> <?php echo twmp_get_svg_icon('loading'); ?></button></div>
                </form>
            </div>
        </div>
        <div class="show-search-order-lookup" style="display: none;"></div>
    </div>
<?php
    return ob_get_clean();
});

add_shortcode('warranty-tracking', function () {
    ob_start();
    $block_attributes = array(
        'endpoint' => 'search_warranty_lookup'
    );
?>
    <div class="warranty-lookup-wrapper" data-settings='<?php echo json_encode($block_attributes) ?>' data-block="warranty-tracking">
        <div class="warranty-lookup">
            <h3 class="title-lookup"><?php echo esc_html__('To check your warranty status, please enter your phone number in the box below and press the “Check” button.', 'taiwebmienphi-plus'); ?></h3>
            <div class="form-warranty-lookup">
                <form class="form" data-gtm-form-interact-id="0">
                    <div class="input"><input type="text" class="search-phone" placeholder="<?php echo esc_attr__('Enter phone number', 'taiwebmienphi-plus'); ?>" required="" data-gtm-form-interact-field-id="0"></div>
                    <div class="button"><button type="submit"><?php echo esc_html__('Check', 'taiwebmienphi-plus'); ?> <?php echo twmp_get_svg_icon('loading') ?></button></div>
                </form>
            </div>
        </div>
        <div class="show-search-warranty-lookup" style="display: none;"></div>
    </div>
<?php
    return ob_get_clean();
});
