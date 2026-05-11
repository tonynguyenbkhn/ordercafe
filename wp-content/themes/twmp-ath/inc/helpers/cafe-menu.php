<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('twmp_cafe_menu_ensure_cart')) {
    function twmp_cafe_menu_ensure_cart()
    {
        if (!function_exists('WC')) {
            return false;
        }

        if (null === WC()->cart) {
            wc_load_cart();
        }

        return (null !== WC()->cart);
    }
}

if (!function_exists('twmp_cafe_menu_get_terms')) {
    function twmp_cafe_menu_get_terms()
    {
        if (!taxonomy_exists('product_cat')) {
            return [];
        }

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        return $terms;
    }
}

if (!function_exists('twmp_cafe_menu_get_products_for_term')) {
    function twmp_cafe_menu_get_products_for_term($term)
    {
        if (is_numeric($term)) {
            $term = get_term((int) $term, 'product_cat');
        }

        if (!$term || is_wp_error($term) || empty($term->slug) || !function_exists('wc_get_products')) {
            return [];
        }

        return wc_get_products([
            'status' => 'publish',
            'limit'  => -1,
            'orderby' => 'menu_order',
            'order'   => 'ASC',
            'category' => [$term->slug],
            'return'  => 'objects',
        ]);
    }
}

if (!function_exists('twmp_cafe_menu_enqueue_assets')) {
    function twmp_cafe_menu_enqueue_assets()
    {
        if (!is_page_template('templates/page-cafe-menu.php')) {
            return;
        }

        $theme_version = wp_get_theme()->get('Version');

        wp_enqueue_style(
            'twmp-cafe-menu',
            get_theme_file_uri('/assets/css/cafe-menu.css'),
            [],
            $theme_version
        );

        wp_enqueue_script(
            'twmp-cafe-menu',
            get_theme_file_uri('/assets/js/cafe-menu.js'),
            [],
            $theme_version,
            true
        );

        wp_localize_script('twmp-cafe-menu', 'twmpCafeMenu', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('twmp_cafe_menu_nonce'),
            'strings' => [
                'addToCart'   => __('Thêm vào giỏ', 'twmp-ath'),
                'chooseAttrs'  => __('Vui lòng chọn đủ tuỳ chọn', 'twmp-ath'),
                'cartUpdated'  => __('Giỏ hàng đã được cập nhật', 'twmp-ath'),
                'cartEmpty'    => __('Giỏ hàng đang trống', 'twmp-ath'),
                'updateError'  => __('Không thể cập nhật giỏ hàng', 'twmp-ath'),
                'addError'     => __('Không thể thêm sản phẩm', 'twmp-ath'),
                'invalidForm'  => __('Dữ liệu sản phẩm không hợp lệ', 'twmp-ath'),
            ],
        ]);
    }
    add_action('wp_enqueue_scripts', 'twmp_cafe_menu_enqueue_assets', 20);
}

if (!function_exists('twmp_cafe_menu_render_note_field')) {
    function twmp_cafe_menu_render_note_field($product_id)
    {
        ?>
        <label class="twmp-cafe-form__field twmp-cafe-form__field--note" for="twmp-cafe-note-<?php echo esc_attr($product_id); ?>">
            <span><?php esc_html_e('Ghi chú', 'twmp-ath'); ?></span>
            <textarea
                id="twmp-cafe-note-<?php echo esc_attr($product_id); ?>"
                name="note"
                rows="2"
                placeholder="<?php echo esc_attr__('Ít đá, không đường, thêm topping...', 'twmp-ath'); ?>"></textarea>
        </label>
        <?php
    }
}

if (!function_exists('twmp_cafe_menu_render_quantity_field')) {
    function twmp_cafe_menu_render_quantity_field($product_id, $default = 1)
    {
        ?>
        <label class="twmp-cafe-form__qty" for="twmp-cafe-qty-<?php echo esc_attr($product_id); ?>">
            <span class="screen-reader-text"><?php esc_html_e('Số lượng', 'twmp-ath'); ?></span>
            <button type="button" class="twmp-cafe-form__qty-btn js-cafe-qty" data-delta="-1" aria-label="<?php esc_attr_e('Giảm số lượng', 'twmp-ath'); ?>">-</button>
            <input
                id="twmp-cafe-qty-<?php echo esc_attr($product_id); ?>"
                class="twmp-cafe-form__qty-input js-cafe-qty-input"
                type="number"
                name="quantity"
                min="1"
                step="1"
                value="<?php echo esc_attr(max(1, (int) $default)); ?>">
            <button type="button" class="twmp-cafe-form__qty-btn js-cafe-qty" data-delta="1" aria-label="<?php esc_attr_e('Tăng số lượng', 'twmp-ath'); ?>">+</button>
        </label>
        <?php
    }
}

if (!function_exists('twmp_cafe_menu_render_simple_form')) {
    function twmp_cafe_menu_render_simple_form(WC_Product $product)
    {
        $product_id = $product->get_id();
        ?>
        <form class="twmp-cafe-form twmp-cafe-form--simple js-cafe-simple-form" data-product-id="<?php echo esc_attr($product_id); ?>">
            <?php twmp_cafe_menu_render_note_field($product_id); ?>
            <div class="twmp-cafe-form__footer">
                <?php twmp_cafe_menu_render_quantity_field($product_id); ?>
                <button type="submit" class="twmp-cafe-form__submit">
                    <?php esc_html_e('Thêm vào giỏ', 'twmp-ath'); ?>
                </button>
            </div>
            <div class="twmp-cafe-form__message" aria-live="polite"></div>
        </form>
        <?php
    }
}

if (!function_exists('twmp_cafe_menu_render_variable_form')) {
    function twmp_cafe_menu_render_variable_form(WC_Product $product)
    {
        $product_id = $product->get_id();
        $attributes  = $product->get_variation_attributes();
        $defaults    = $product->get_default_attributes();
        $variations  = [];

        foreach ($product->get_available_variations() as $variation) {
            if (empty($variation['variation_id'])) {
                continue;
            }

            $variations[] = [
                'variation_id' => absint($variation['variation_id']),
                'attributes'    => array_map('strval', isset($variation['attributes']) ? $variation['attributes'] : []),
                'is_in_stock'   => !empty($variation['is_in_stock']),
                'price_html'    => isset($variation['price_html']) ? wp_kses_post($variation['price_html']) : '',
            ];
        }
        ?>
        <form
            class="twmp-cafe-form twmp-cafe-form--variable js-cafe-variable-form"
            data-product-id="<?php echo esc_attr($product_id); ?>"
            data-variations="<?php echo esc_attr(wp_json_encode($variations)); ?>">
            <div class="twmp-cafe-form__fields">
                <?php foreach ($attributes as $attribute_name => $options) : ?>
                    <?php
                    $field_name = wc_variation_attribute_name($attribute_name);
                    $label      = wc_attribute_label($attribute_name, $product);
                    $selected   = isset($defaults[$attribute_name]) ? $defaults[$attribute_name] : '';
                    ?>
                    <label class="twmp-cafe-form__field" for="twmp-cafe-<?php echo esc_attr($product_id . '-' . $field_name); ?>">
                        <span><?php echo esc_html($label); ?></span>
                        <select
                            id="twmp-cafe-<?php echo esc_attr($product_id . '-' . $field_name); ?>"
                            name="<?php echo esc_attr($field_name); ?>"
                            class="twmp-cafe-form__select js-cafe-variation-attr">
                            <option value=""><?php echo esc_html(sprintf(__('Chọn %s', 'twmp-ath'), strtolower($label))); ?></option>
                            <?php
                            if (taxonomy_exists($attribute_name)) {
                                $terms = wc_get_product_terms($product_id, $attribute_name, ['fields' => 'all']);
                                foreach ($terms as $term) {
                                    ?>
                                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected, $term->slug); ?>>
                                        <?php echo esc_html($term->name); ?>
                                    </option>
                                    <?php
                                }
                            } else {
                                foreach ($options as $option) {
                                    ?>
                                    <option value="<?php echo esc_attr($option); ?>" <?php selected($selected, $option); ?>>
                                        <?php echo esc_html($option); ?>
                                    </option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php twmp_cafe_menu_render_note_field($product_id); ?>
            <div class="twmp-cafe-form__footer">
                <?php twmp_cafe_menu_render_quantity_field($product_id); ?>
                <button type="submit" class="twmp-cafe-form__submit">
                    <?php esc_html_e('Thêm vào giỏ', 'twmp-ath'); ?>
                </button>
            </div>
            <div class="twmp-cafe-form__message" aria-live="polite"></div>
        </form>
        <?php
    }
}

if (!function_exists('twmp_cafe_menu_render_product_card')) {
    function twmp_cafe_menu_render_product_card(WC_Product $product)
    {
        $product_id   = $product->get_id();
        $title        = $product->get_name();
        $image_id     = $product->get_image_id();
        $price_html   = $product->get_price_html();
        $description  = wp_trim_words(wp_strip_all_tags((string) $product->get_short_description()), 18, '...');
        $is_variable  = $product->is_type('variable');
        $is_out_stock = !$product->is_in_stock();
        ?>
        <article class="twmp-cafe-card <?php echo $is_variable ? 'twmp-cafe-card--variable' : 'twmp-cafe-card--simple'; ?>">
            <div class="twmp-cafe-card__media">
                <?php
                if ($image_id) {
                    echo wp_get_attachment_image($image_id, 'woocommerce_thumbnail', false, [
                        'class' => 'twmp-cafe-card__image',
                        'alt'   => esc_attr($title),
                    ]);
                } else {
                    echo wc_placeholder_img('woocommerce_thumbnail', [
                        'class' => 'twmp-cafe-card__image',
                    ]);
                }
                ?>
                <?php if ($is_out_stock) : ?>
                    <span class="twmp-cafe-card__badge"><?php esc_html_e('Hết hàng', 'twmp-ath'); ?></span>
                <?php endif; ?>
            </div>
            <div class="twmp-cafe-card__body">
                <div class="twmp-cafe-card__top">
                    <h3 class="twmp-cafe-card__title"><?php echo esc_html($title); ?></h3>
                    <?php if ($price_html) : ?>
                        <div class="twmp-cafe-card__price"><?php echo wp_kses_post($price_html); ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($description) : ?>
                    <p class="twmp-cafe-card__description"><?php echo esc_html($description); ?></p>
                <?php endif; ?>
                <div class="twmp-cafe-card__actions">
                    <?php
                    if ($is_out_stock) {
                        ?>
                        <span class="twmp-cafe-card__sold-out"><?php esc_html_e('Tạm hết', 'twmp-ath'); ?></span>
                        <?php
                    } elseif ($is_variable) {
                        twmp_cafe_menu_render_variable_form($product);
                    } else {
                        twmp_cafe_menu_render_simple_form($product);
                    }
                    ?>
                </div>
            </div>
        </article>
        <?php
    }
}

if (!function_exists('twmp_cafe_menu_render_cart_items')) {
    function twmp_cafe_menu_render_cart_items()
    {
        if (!twmp_cafe_menu_ensure_cart()) {
            return '';
        }

        ob_start();
        ?>
        <div class="twmp-cafe-cart__content">
            <?php if (WC()->cart->is_empty()) : ?>
                <div class="twmp-cafe-cart__empty">
                    <p><?php esc_html_e('Giỏ hàng đang trống.', 'twmp-ath'); ?></p>
                    <p><?php esc_html_e('Hãy chọn một món cafe để bắt đầu.', 'twmp-ath'); ?></p>
                </div>
            <?php else : ?>
                <ul class="twmp-cafe-cart__list">
                    <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) : ?>
                        <?php
                        $_product = isset($cart_item['data']) ? $cart_item['data'] : false;
                        if (!$_product || !$_product->exists()) {
                            continue;
                        }

                        $product_name = $_product->get_name();
                        $thumbnail     = $_product->get_image('woocommerce_thumbnail', ['class' => 'twmp-cafe-cart__thumb']);
                        $note          = isset($cart_item['twmp_note']) ? (string) $cart_item['twmp_note'] : '';
                        $permalink     = $_product->is_visible() ? $_product->get_permalink($cart_item) : '';
                        ?>
                        <li class="twmp-cafe-cart__item">
                            <button
                                type="button"
                                class="twmp-cafe-cart__remove js-cafe-cart-remove"
                                data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                aria-label="<?php echo esc_attr(sprintf(__('Xoá %s', 'twmp-ath'), $product_name)); ?>">
                                &times;
                            </button>
                            <div class="twmp-cafe-cart__item-wrap">
                                <?php if ($permalink) : ?>
                                    <a class="twmp-cafe-cart__media" href="<?php echo esc_url($permalink); ?>">
                                        <?php echo wp_kses_post($thumbnail); ?>
                                    </a>
                                <?php else : ?>
                                    <div class="twmp-cafe-cart__media">
                                        <?php echo wp_kses_post($thumbnail); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="twmp-cafe-cart__meta">
                                    <h4 class="twmp-cafe-cart__title">
                                        <?php echo esc_html($product_name); ?>
                                    </h4>
                                    <div class="twmp-cafe-cart__variations">
                                        <?php echo wp_kses_post(wc_get_formatted_cart_item_data($cart_item)); ?>
                                    </div>
                                    <?php if (!empty($note)) : ?>
                                        <div class="twmp-cafe-cart__note">
                                            <?php echo esc_html($note); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="twmp-cafe-cart__controls">
                                        <div class="twmp-cafe-cart__qty">
                                            <button type="button" class="twmp-cafe-cart__qty-btn js-cafe-cart-qty" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-delta="-1" aria-label="<?php esc_attr_e('Giảm số lượng', 'twmp-ath'); ?>">-</button>
                                            <span class="twmp-cafe-cart__qty-value"><?php echo esc_html((int) $cart_item['quantity']); ?></span>
                                            <button type="button" class="twmp-cafe-cart__qty-btn js-cafe-cart-qty" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-delta="1" aria-label="<?php esc_attr_e('Tăng số lượng', 'twmp-ath'); ?>">+</button>
                                        </div>
                                        <div class="twmp-cafe-cart__line-total">
                                            <?php echo wp_kses_post(WC()->cart->get_product_subtotal($_product, (int) $cart_item['quantity'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="twmp-cafe-cart__summary">
                    <div class="twmp-cafe-cart__summary-row">
                        <span><?php esc_html_e('Tạm tính', 'twmp-ath'); ?></span>
                        <strong class="js-cafe-cart-subtotal"><?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?></strong>
                    </div>
                    <div class="twmp-cafe-cart__summary-row twmp-cafe-cart__summary-row--total">
                        <span><?php esc_html_e('Tổng', 'twmp-ath'); ?></span>
                        <strong class="js-cafe-cart-total"><?php echo wp_kses_post(WC()->cart->get_total()); ?></strong>
                    </div>
                    <a class="twmp-cafe-cart__checkout button" href="<?php echo esc_url(wc_get_checkout_url()); ?>">
                        <?php esc_html_e('Đặt hàng', 'twmp-ath'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('twmp_cafe_menu_render_cart_sidebar')) {
    function twmp_cafe_menu_render_cart_sidebar()
    {
        if (!twmp_cafe_menu_ensure_cart()) {
            return '';
        }

        $count = WC()->cart->get_cart_contents_count();

        ob_start();
        ?>
        <aside class="twmp-cafe-cart" data-cafe-cart aria-label="<?php esc_attr_e('Giỏ hàng', 'twmp-ath'); ?>">
            <div class="twmp-cafe-cart__header">
                <div class="twmp-cafe-cart__heading">
                    <p class="twmp-cafe-cart__eyebrow"><?php esc_html_e('My Order', 'twmp-ath'); ?></p>
                    <h2><?php esc_html_e('Giỏ hàng', 'twmp-ath'); ?></h2>
                </div>
                <button type="button" class="twmp-cafe-cart__close js-cafe-cart-toggle" aria-label="<?php esc_attr_e('Đóng giỏ hàng', 'twmp-ath'); ?>">
                    &times;
                </button>
            </div>
            <div class="twmp-cafe-cart__status">
                <span class="twmp-cafe-cart__count-label"><?php esc_html_e('Số món', 'twmp-ath'); ?></span>
                <strong class="js-cafe-cart-count" data-cafe-cart-count><?php echo esc_html((int) $count); ?></strong>
            </div>
            <?php echo twmp_cafe_menu_render_cart_items(); ?>
        </aside>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('twmp_cafe_menu_render_cart_shell')) {
    function twmp_cafe_menu_render_cart_shell()
    {
        if (!twmp_cafe_menu_ensure_cart()) {
            return '';
        }

        return [
            'html'     => twmp_cafe_menu_render_cart_sidebar(),
            'count'    => WC()->cart->get_cart_contents_count(),
            'subtotal' => WC()->cart->get_cart_subtotal(),
            'total'    => WC()->cart->get_total(),
        ];
    }
}

if (!function_exists('twmp_cafe_menu_ajax_get_cart')) {
    function twmp_cafe_menu_ajax_get_cart()
    {
        check_ajax_referer('twmp_cafe_menu_nonce', 'nonce');

        if (!twmp_cafe_menu_ensure_cart()) {
            wp_send_json_error(['message' => __('Không thể tải giỏ hàng.', 'twmp-ath')], 400);
        }

        wp_send_json_success(twmp_cafe_menu_render_cart_shell());
    }
    add_action('wp_ajax_twmp_cafe_menu_get_cart', 'twmp_cafe_menu_ajax_get_cart');
    add_action('wp_ajax_nopriv_twmp_cafe_menu_get_cart', 'twmp_cafe_menu_ajax_get_cart');
}

if (!function_exists('twmp_cafe_menu_sanitize_variation_data')) {
    function twmp_cafe_menu_sanitize_variation_data($variation_data)
    {
        $sanitized = [];

        foreach ((array) $variation_data as $key => $value) {
            $sanitized[sanitize_key($key)] = wc_clean(wp_unslash($value));
        }

        return $sanitized;
    }
}

if (!function_exists('twmp_cafe_menu_ajax_add_to_cart')) {
    function twmp_cafe_menu_ajax_add_to_cart()
    {
        check_ajax_referer('twmp_cafe_menu_nonce', 'nonce');

        if (!twmp_cafe_menu_ensure_cart()) {
            wp_send_json_error(['message' => __('Không thể khởi tạo giỏ hàng.', 'twmp-ath')], 400);
        }

        $product_id   = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $quantity     = isset($_POST['quantity']) ? max(1, absint($_POST['quantity'])) : 1;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $note         = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
        $variation    = isset($_POST['variation']) ? twmp_cafe_menu_sanitize_variation_data((array) $_POST['variation']) : [];

        if (!$product_id || !function_exists('wc_get_product')) {
            wp_send_json_error(['message' => __('Dữ liệu sản phẩm không hợp lệ.', 'twmp-ath')], 400);
        }

        $product = wc_get_product($product_id);

        if (!$product || !$product->is_purchasable()) {
            wp_send_json_error(['message' => __('Sản phẩm này không thể mua.', 'twmp-ath')], 400);
        }

        $cart_item_data = [];

        if ('' !== $note) {
            $cart_item_data['twmp_note'] = $note;
        }

        $added = WC()->cart->add_to_cart(
            $product_id,
            $quantity,
            $variation_id,
            $variation,
            $cart_item_data
        );

        if (!$added) {
            wp_send_json_error(['message' => __('Không thể thêm sản phẩm vào giỏ.', 'twmp-ath')], 400);
        }

        WC()->cart->calculate_totals();

        wp_send_json_success([
            'message' => __('Đã thêm vào giỏ hàng.', 'twmp-ath'),
            'cart'    => twmp_cafe_menu_render_cart_shell(),
        ]);
    }
    add_action('wp_ajax_twmp_cafe_menu_add_to_cart', 'twmp_cafe_menu_ajax_add_to_cart');
    add_action('wp_ajax_nopriv_twmp_cafe_menu_add_to_cart', 'twmp_cafe_menu_ajax_add_to_cart');
}

if (!function_exists('twmp_cafe_menu_ajax_update_cart')) {
    function twmp_cafe_menu_ajax_update_cart()
    {
        check_ajax_referer('twmp_cafe_menu_nonce', 'nonce');

        if (!twmp_cafe_menu_ensure_cart()) {
            wp_send_json_error(['message' => __('Không thể khởi tạo giỏ hàng.', 'twmp-ath')], 400);
        }

        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field(wp_unslash($_POST['cart_item_key'])) : '';
        $quantity      = isset($_POST['quantity']) ? absint($_POST['quantity']) : 0;

        if (!$cart_item_key || !isset(WC()->cart->cart_contents[$cart_item_key])) {
            wp_send_json_error(['message' => __('Không tìm thấy món trong giỏ.', 'twmp-ath')], 404);
        }

        if ($quantity <= 0) {
            WC()->cart->remove_cart_item($cart_item_key);
        } else {
            WC()->cart->set_quantity($cart_item_key, $quantity, true);
        }

        WC()->cart->calculate_totals();

        wp_send_json_success([
            'message' => __('Giỏ hàng đã được cập nhật.', 'twmp-ath'),
            'cart'    => twmp_cafe_menu_render_cart_shell(),
        ]);
    }
    add_action('wp_ajax_twmp_cafe_menu_update_cart', 'twmp_cafe_menu_ajax_update_cart');
    add_action('wp_ajax_nopriv_twmp_cafe_menu_update_cart', 'twmp_cafe_menu_ajax_update_cart');
}

if (!function_exists('twmp_cafe_menu_ajax_remove_cart')) {
    function twmp_cafe_menu_ajax_remove_cart()
    {
        check_ajax_referer('twmp_cafe_menu_nonce', 'nonce');

        if (!twmp_cafe_menu_ensure_cart()) {
            wp_send_json_error(['message' => __('Không thể khởi tạo giỏ hàng.', 'twmp-ath')], 400);
        }

        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field(wp_unslash($_POST['cart_item_key'])) : '';

        if (!$cart_item_key || !isset(WC()->cart->cart_contents[$cart_item_key])) {
            wp_send_json_error(['message' => __('Không tìm thấy món trong giỏ.', 'twmp-ath')], 404);
        }

        WC()->cart->remove_cart_item($cart_item_key);
        WC()->cart->calculate_totals();

        wp_send_json_success([
            'message' => __('Đã xoá món khỏi giỏ hàng.', 'twmp-ath'),
            'cart'    => twmp_cafe_menu_render_cart_shell(),
        ]);
    }
    add_action('wp_ajax_twmp_cafe_menu_remove_cart', 'twmp_cafe_menu_ajax_remove_cart');
    add_action('wp_ajax_nopriv_twmp_cafe_menu_remove_cart', 'twmp_cafe_menu_ajax_remove_cart');
}

add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['twmp_note'])) {
        $item_data[] = [
            'name'  => __('Ghi chú', 'twmp-ath'),
            'value' => wc_clean($cart_item['twmp_note']),
        ];
    }

    return $item_data;
}, 10, 2);

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {
    if (!empty($values['twmp_note'])) {
        $item->add_meta_data(__('Ghi chú', 'twmp-ath'), wc_clean($values['twmp_note']), true);
    }
}, 10, 3);
