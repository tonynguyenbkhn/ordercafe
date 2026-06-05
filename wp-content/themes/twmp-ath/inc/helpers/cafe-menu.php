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

        return null !== WC()->cart;
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
            'status'  => 'publish',
            'limit'   => -1,
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

        wp_localize_script('twmp-frontend', 'twmpCafeMenu', [
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
                'selectOption' => __('Chọn', 'twmp-ath'),
                'nextStep'     => __('Tiếp theo', 'twmp-ath'),
                'prevStep'     => __('Quay lại', 'twmp-ath'),
                'openProduct'  => __('Chọn món', 'twmp-ath'),
                'editOptions'  => __('Sửa lựa chọn', 'twmp-ath'),
                'closed'       => __('Món này tạm hết hàng', 'twmp-ath'),
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

if (!function_exists('twmp_cafe_menu_get_product_attribute_steps')) {
    function twmp_cafe_menu_get_product_attribute_steps(WC_Product $product)
    {
        if (!$product->is_type('variable') || !method_exists($product, 'get_variation_attributes')) {
            return [];
        }

        $product_id = $product->get_id();
        $attributes = $product->get_variation_attributes();
        $defaults   = $product->get_default_attributes();
        $steps      = [];

        foreach ($attributes as $attribute_name => $options) {
            $field_name = wc_variation_attribute_name($attribute_name);
            $label      = wc_attribute_label($attribute_name, $product);
            $selected   = isset($defaults[$attribute_name]) ? (string) $defaults[$attribute_name] : '';
            $choices    = [];

            if (taxonomy_exists($attribute_name)) {
                $terms = wc_get_product_terms($product_id, $attribute_name, ['fields' => 'all']);

                foreach ($terms as $term) {
                    $choices[] = [
                        'value' => (string) $term->slug,
                        'label' => (string) $term->name,
                    ];
                }
            } else {
                foreach ((array) $options as $option) {
                    $choices[] = [
                        'value' => (string) $option,
                        'label' => (string) $option,
                    ];
                }
            }

            $steps[] = [
                'attribute_name' => $attribute_name,
                'field_name'     => $field_name,
                'label'          => $label,
                'selected'       => $selected,
                'choices'        => $choices,
            ];
        }

        return $steps;
    }
}

if (!function_exists('twmp_cafe_menu_get_product_variations')) {
    function twmp_cafe_menu_get_product_variations(WC_Product $product)
    {
        if (!$product->is_type('variable') || !method_exists($product, 'get_available_variations')) {
            return [];
        }

        $variations = [];

        foreach ($product->get_available_variations() as $variation) {
            if (empty($variation['variation_id'])) {
                continue;
            }

            $variations[] = [
                'variation_id' => absint($variation['variation_id']),
                'attributes'   => array_map('strval', isset($variation['attributes']) ? $variation['attributes'] : []),
                'is_in_stock'  => !empty($variation['is_in_stock']),
                'price_html'   => isset($variation['price_html']) ? wp_kses_post($variation['price_html']) : '',
            ];
        }

        return $variations;
    }
}

if (!function_exists('twmp_cafe_menu_get_product_staff_notes')) {
    function twmp_cafe_menu_get_product_staff_notes(WC_Product $product)
    {
        $notes = [];

        foreach ((array) $product->get_attributes() as $attribute) {
            if (!$attribute instanceof WC_Product_Attribute) {
                continue;
            }

            $attribute_name = (string) $attribute->get_name();
            if ($attribute_name === '') {
                continue;
            }

            $label = wc_attribute_label($attribute_name, $product);
            $values = [];

            foreach ((array) $attribute->get_options() as $option) {
                if (is_numeric($option) && taxonomy_exists($attribute_name)) {
                    $term = get_term((int) $option, $attribute_name);
                    if ($term && !is_wp_error($term)) {
                        $values[] = (string) $term->name;
                    }
                    continue;
                }

                $values[] = (string) $option;
            }

            $notes[] = [
                'name'   => $attribute_name,
                'label'  => $label,
                'values' => $values,
            ];
        }

        return $notes;
    }
}

if (!function_exists('twmp_cafe_menu_get_product_staff_note_steps')) {
    function twmp_cafe_menu_get_product_staff_note_steps(WC_Product $product)
    {
        $product_id = $product->get_id();
        $steps      = [];

        foreach ((array) $product->get_attributes() as $attribute) {
            if (!$attribute instanceof WC_Product_Attribute) {
                continue;
            }

            if ($attribute->get_variation()) {
                continue;
            }

            $attribute_name = (string) $attribute->get_name();
            if ($attribute_name === '') {
                continue;
            }

            $label = wc_attribute_label($attribute_name, $product);
            $choices = [];

            if (taxonomy_exists($attribute_name)) {
                $terms = wc_get_product_terms($product_id, $attribute_name, ['fields' => 'all']);

                if (empty($terms)) {
                    $terms = get_terms([
                        'taxonomy'   => $attribute_name,
                        'hide_empty' => false,
                        'orderby'    => 'menu_order',
                        'order'      => 'ASC',
                    ]);
                }

                foreach ((array) $terms as $term) {
                    if (!$term || is_wp_error($term)) {
                        continue;
                    }

                    $choices[] = [
                        'value' => (string) $term->slug,
                        'label' => (string) $term->name,
                    ];
                }
            } else {
                foreach ((array) $attribute->get_options() as $option) {
                    $choices[] = [
                        'value' => (string) $option,
                        'label' => (string) $option,
                    ];
                }
            }

            $steps[] = [
                'attribute_name' => $attribute_name,
                'field_name'     => $attribute_name,
                'label'          => $label,
                'choices'        => $choices,
            ];
        }

        return $steps;
    }
}

if (!function_exists('twmp_cafe_menu_get_product_modal_data')) {
    function twmp_cafe_menu_get_product_modal_data(WC_Product $product)
    {
        $product_id = $product->get_id();
        $description = trim((string) $product->get_short_description());

        return [
            'product_id'       => $product_id,
            'name'             => $product->get_name(),
            'price_html'       => wp_kses_post($product->get_price_html()),
            'description'      => wp_kses_post($description ? wpautop(wp_strip_all_tags($description)) : ''),
            'image_html'       => '',
            'is_variable'      => $product->is_type('variable'),
            'is_purchasable'   => $product->is_purchasable(),
            'is_in_stock'      => $product->is_in_stock(),
            'quantity_default' => 1,
            'note_placeholder' => __('Ít đá, không đường, thêm topping...', 'twmp-ath'),
            'staff_notes'      => twmp_cafe_menu_get_product_staff_notes($product),
            'staff_note_steps' => twmp_cafe_menu_get_product_staff_note_steps($product),
            'steps'            => twmp_cafe_menu_get_product_attribute_steps($product),
            'variations'       => twmp_cafe_menu_get_product_variations($product),
        ];
    }
}

if (!function_exists('twmp_cafe_menu_get_cart_item_payload')) {
    function twmp_cafe_menu_get_cart_item_payload(array $cart_item, $cart_item_key = '')
    {
        $_product = isset($cart_item['data']) ? $cart_item['data'] : false;
        if (!$_product || !$_product->exists()) {
            return [];
        }

        $base_product = $_product->is_type('variation') && $_product->get_parent_id()
            ? wc_get_product($_product->get_parent_id())
            : $_product;

        if (!$base_product) {
            return [];
        }

        $payload = twmp_cafe_menu_get_product_modal_data($base_product);
        $payload['cart_item_key'] = (string) $cart_item_key;
        $payload['quantity'] = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;
        $payload['note'] = isset($cart_item['twmp_note']) ? (string) $cart_item['twmp_note'] : '';
        $payload['staff_notes'] = isset($cart_item['twmp_staff_notes']) && is_array($cart_item['twmp_staff_notes'])
            ? (array) $cart_item['twmp_staff_notes']
            : [];
        $payload['variation_id'] = isset($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0;
        $payload['variation'] = isset($cart_item['variation']) && is_array($cart_item['variation'])
            ? array_map('strval', $cart_item['variation'])
            : [];

        return $payload;
    }
}

if (!function_exists('twmp_cafe_menu_render_product_card')) {
    function twmp_cafe_menu_render_product_card(WC_Product $product)
    {
        $product_id   = $product->get_id();
        $title        = $product->get_name();
        $price_html   = $product->get_price_html();
        $description  = wp_trim_words(wp_strip_all_tags((string) $product->get_short_description()), 18, '...');
        $is_out_stock = !$product->is_in_stock();
        $modal_data   = twmp_cafe_menu_get_product_modal_data($product);
        ?>
        <article
            class="twmp-cafe-card <?php echo $product->is_type('variable') ? 'twmp-cafe-card--variable' : 'twmp-cafe-card--simple'; ?>"
            data-cafe-product="<?php echo esc_attr(wp_json_encode($modal_data)); ?>">
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
                    <?php if ($is_out_stock) : ?>
                        <span class="twmp-cafe-card__sold-out"><?php esc_html_e('Tạm hết', 'twmp-ath'); ?></span>
                    <?php else : ?>
                        <button type="button" class="twmp-cafe-card__open js-cafe-product-open">
                            <?php esc_html_e('+', 'twmp-ath'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php
    }
}

if (!function_exists('twmp_cafe_menu_render_product_modal_shell')) {
    function twmp_cafe_menu_render_product_modal_shell()
    {
        if (!twmp_cafe_menu_ensure_cart()) {
            return '';
        }

        ob_start();
        ?>
        <div class="twmp-cafe-modal" data-cafe-modal hidden>
            <div class="twmp-cafe-modal__backdrop js-cafe-modal-close" aria-hidden="true"></div>
            <div class="twmp-cafe-modal__panel" role="dialog" aria-modal="true" aria-labelledby="twmp-cafe-modal-title">
                <button type="button" class="twmp-cafe-modal__close js-cafe-modal-close" aria-label="<?php esc_attr_e('Đóng', 'twmp-ath'); ?>">
                    &times;
                </button>
                <div class="twmp-cafe-modal__layout">
                    <div class="twmp-cafe-modal__media" data-cafe-modal-media></div>
                    <div class="twmp-cafe-modal__content">
                        <div class="twmp-cafe-modal__heading-row">
                            <h2 id="twmp-cafe-modal-title" class="twmp-cafe-modal__title" data-cafe-modal-title></h2>
                            <div class="twmp-cafe-modal__price" data-cafe-modal-price></div>
                        </div>
                        <!-- <div class="twmp-cafe-modal__description" data-cafe-modal-description></div> -->
                        <!-- <div class="twmp-cafe-modal__progress" data-cafe-modal-progress></div> -->
                        <div class="twmp-cafe-modal__steps" data-cafe-modal-steps></div>
                        <div class="twmp-cafe-modal__summary" data-cafe-modal-summary></div>
                        <div class="twmp-cafe-modal__note">
                            <label class="twmp-cafe-form__field twmp-cafe-form__field--note" for="twmp-cafe-note">
                                <span><?php esc_html_e('Ghi chú', 'twmp-ath'); ?></span>
                                <textarea
                                    id="twmp-cafe-note"
                                    name="note"
                                    rows="2"
                                    data-cafe-modal-note
                                    placeholder="<?php echo esc_attr__('Ít đá, không đường, thêm topping...', 'twmp-ath'); ?>"></textarea>
                            </label>
                        </div>
                        <div class="twmp-cafe-modal__footer">
                            <label class="twmp-cafe-modal__qty" data-cafe-modal-qty-field>
                                <button type="button" class="twmp-cafe-modal__qty-btn js-cafe-modal-qty" data-delta="-1" aria-label="<?php esc_attr_e('Giảm số lượng', 'twmp-ath'); ?>">-</button>
                                <input type="number" min="1" step="1" value="1" data-cafe-modal-qty>
                                <button type="button" class="twmp-cafe-modal__qty-btn js-cafe-modal-qty" data-delta="1" aria-label="<?php esc_attr_e('Tăng số lượng', 'twmp-ath'); ?>">+</button>
                            </label>
                            <div class="twmp-cafe-modal__actions">
                                <button type="button" class="twmp-cafe-modal__button twmp-cafe-modal__button--primary js-cafe-modal-add">
                                    <?php esc_html_e('Thêm vào giỏ', 'twmp-ath'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="twmp-cafe-modal__message" data-cafe-modal-message aria-live="polite"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
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
                    <p><?php esc_html_e('Hãy chọn một món để bắt đầu.', 'twmp-ath'); ?></p>
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
                        $note         = isset($cart_item['twmp_note']) ? (string) $cart_item['twmp_note'] : '';
                        $staff_notes  = isset($cart_item['twmp_staff_notes']) && is_array($cart_item['twmp_staff_notes']) ? (array) $cart_item['twmp_staff_notes'] : [];
                        $cart_payload  = twmp_cafe_menu_get_cart_item_payload($cart_item, $cart_item_key);
                        ?>
                        <li class="twmp-cafe-cart__item" data-cafe-cart-item="<?php echo esc_attr(wp_json_encode($cart_payload)); ?>">
                            <button
                                type="button"
                                class="twmp-cafe-cart__remove js-cafe-cart-remove"
                                data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                aria-label="<?php echo esc_attr(sprintf(__('Xoá %s', 'twmp-ath'), $product_name)); ?>">
                                &times;
                            </button>
                            <div class="twmp-cafe-cart__item-wrap">
                                <div class="twmp-cafe-cart__meta">
                                    <div class="twmp-cafe-cart__title-row">
                                        <h4 class="twmp-cafe-cart__title"><?php echo esc_html($product_name); ?></h4>
                                        <?php if (!empty($cart_payload)) : ?>
                                            <button
                                                type="button"
                                                class="twmp-cafe-cart__edit js-cafe-cart-edit"
                                                data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                                aria-label="<?php echo esc_attr(sprintf(__('Sửa lựa chọn %s', 'twmp-ath'), $product_name)); ?>">
                                                <?php esc_html_e('Sửa', 'twmp-ath'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="twmp-cafe-cart__variations">
                                        <?php echo wp_kses_post(wc_get_formatted_cart_item_data($cart_item)); ?>
                                    </div>
                                    <?php if (!empty($note)) : ?>
                                        <div class="twmp-cafe-cart__note">
                                            <?php echo esc_html($note); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($staff_notes)) : ?>
                                        <div class="twmp-cafe-cart__variations twmp-cafe-cart__variations--staff">
                                            <?php foreach ($staff_notes as $key => $value) : ?>
                                                <span class="twmp-cafe-cart__staff-note">
                                                    <?php echo esc_html(sprintf('%s: %s', wc_attribute_label($key), $value)); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="twmp-cafe-cart__controls">
                                        <div class="twmp-cafe-cart__qty">
                                            <button type="button" class="twmp-cafe-cart__qty-btn js-cafe-cart-qty" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-delta="-1" aria-label="<?php esc_attr_e('Giảm số lượng', 'twmp-ath'); ?>">-</button>
                                            <input
                                                type="number"
                                                class="twmp-cafe-cart__qty-value js-cafe-cart-qty-value"
                                                value="<?php echo esc_attr((int) $cart_item['quantity']); ?>"
                                                min="0"
                                                step="1"
                                                inputmode="numeric"
                                                readonly>
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
        $staff_notes  = isset($_POST['staff_notes']) ? twmp_cafe_menu_sanitize_variation_data((array) $_POST['staff_notes']) : [];
        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field(wp_unslash($_POST['cart_item_key'])) : '';

        if (!$product_id || !function_exists('wc_get_product')) {
            wp_send_json_error(['message' => __('Dữ liệu sản phẩm không hợp lệ.', 'twmp-ath')], 400);
        }

        $product = wc_get_product($product_id);

        if (!$product || !$product->is_purchasable()) {
            wp_send_json_error(['message' => __('Sản phẩm này không thể mua.', 'twmp-ath')], 400);
        }

        $existing_cart_item = '';
        if ($cart_item_key && isset(WC()->cart->cart_contents[$cart_item_key])) {
            $existing_cart_item = WC()->cart->cart_contents[$cart_item_key];
            WC()->cart->remove_cart_item($cart_item_key);
        }

        $cart_item_data = [];

        if ('' !== $note) {
            $cart_item_data['twmp_note'] = $note;
        }

        if (!empty($staff_notes)) {
            $cart_item_data['twmp_staff_notes'] = $staff_notes;
        }

        $added = WC()->cart->add_to_cart(
            $product_id,
            $quantity,
            $variation_id,
            $variation,
            $cart_item_data
        );

        if (!$added) {
            if (!empty($existing_cart_item) && isset($existing_cart_item['product_id'])) {
                $restore_cart_item_data = [];

                if (!empty($existing_cart_item['twmp_note'])) {
                    $restore_cart_item_data['twmp_note'] = (string) $existing_cart_item['twmp_note'];
                }

                if (!empty($existing_cart_item['twmp_staff_notes']) && is_array($existing_cart_item['twmp_staff_notes'])) {
                    $restore_cart_item_data['twmp_staff_notes'] = (array) $existing_cart_item['twmp_staff_notes'];
                }

                WC()->cart->add_to_cart(
                    absint($existing_cart_item['product_id']),
                    isset($existing_cart_item['quantity']) ? max(1, absint($existing_cart_item['quantity'])) : 1,
                    isset($existing_cart_item['variation_id']) ? absint($existing_cart_item['variation_id']) : 0,
                    isset($existing_cart_item['variation']) && is_array($existing_cart_item['variation']) ? (array) $existing_cart_item['variation'] : [],
                    $restore_cart_item_data
                );
                WC()->cart->calculate_totals();
            }

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

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {
    if (!empty($values['twmp_note'])) {
        $item->add_meta_data(__('Ghi chú', 'twmp-ath'), wc_clean($values['twmp_note']), true);
    }

    if (!empty($values['twmp_staff_notes']) && is_array($values['twmp_staff_notes'])) {
        foreach ($values['twmp_staff_notes'] as $key => $value) {
            $label = wc_attribute_label($key);
            if ($label === $key) {
                $label = ucwords(str_replace(['pa_', '-', '_'], ['',' ', ' '], (string) $key));
            }

            $item->add_meta_data($label, wc_clean($value), false);
        }
    }
}, 10, 3);

if (!function_exists('twmp_cafe_menu_render_bottom_nav')) {
    function twmp_cafe_menu_render_bottom_nav()
    {
        if (is_admin()) {
            return;
        }

        ?>
        <nav class="twmp-bottom-nav" role="navigation" aria-label="<?php esc_attr_e('Thanh điều hướng dưới cùng', 'twmp-ath'); ?>">
            <ul class="twmp-bottom-nav__list">
                <li class="twmp-bottom-nav__item">
                    <a class="twmp-bottom-nav__link" href="<?php echo esc_url(home_url('/')); ?>">
                        <svg viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>menu_navigation_grid [#1529]</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-99.000000, -200.000000)" fill="#ffffff"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M60.85,51 L57.7,51 C55.96015,51 54.55,52.343 54.55,54 L54.55,57 C54.55,58.657 55.96015,60 57.7,60 L60.85,60 C62.58985,60 64,58.657 64,57 L64,54 C64,52.343 62.58985,51 60.85,51 M49.3,51 L46.15,51 C44.41015,51 43,52.343 43,54 L43,57 C43,58.657 44.41015,60 46.15,60 L49.3,60 C51.03985,60 52.45,58.657 52.45,57 L52.45,54 C52.45,52.343 51.03985,51 49.3,51 M60.85,40 L57.7,40 C55.96015,40 54.55,41.343 54.55,43 L54.55,46 C54.55,47.657 55.96015,49 57.7,49 L60.85,49 C62.58985,49 64,47.657 64,46 L64,43 C64,41.343 62.58985,40 60.85,40 M52.45,43 L52.45,46 C52.45,47.657 51.03985,49 49.3,49 L46.15,49 C44.41015,49 43,47.657 43,46 L43,43 C43,41.343 44.41015,40 46.15,40 L49.3,40 C51.03985,40 52.45,41.343 52.45,43" id="menu_navigation_grid-[#1529]"> </path> </g> </g> </g> </g></svg>
                        <?php esc_html_e('Menu', 'twmp-ath'); ?>
                    </a>
                </li>
                <li class="twmp-bottom-nav__item">
                    <a class="twmp-bottom-nav__link" href="<?php echo esc_url(home_url('/staff-orders/')); ?>">
                        <svg fill="#ffffff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" enable-background="new 0 0 52 52" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M39.3,26.9c0,1-0.9,1.9-1.9,1.9H14.6c-1,0-1.9-0.9-1.9-1.9V25c0-1,0.9-1.9,1.9-1.9h22.9c1,0,1.9,0.9,1.9,1.9 v1.9H39.3z M35.5,38.3c0,1-0.9,1.9-1.9,1.9h-19c-1,0-1.9-0.9-1.9-1.9v-1.9c0-1,0.9-1.9,1.9-1.9h19.1c1,0,1.9,0.9,1.9,1.9v1.9H35.5z M12.7,13.5c0-1,0.9-1.9,1.9-1.9h19.1c1,0,1.9,0.9,1.9,1.9v1.9c0,1-0.9,1.9-1.9,1.9H14.6c-1,0-1.9-0.9-1.9-1.9 C12.7,15.4,12.7,13.5,12.7,13.5z M41.2,4H10.8C7.6,4,5,6.6,5,9.7v32.4c0,3.1,2.6,5.7,5.7,5.7h30.5c3.1,0,5.7-2.6,5.7-5.7V9.7 C47,6.6,44.4,4,41.2,4z"></path> </g></svg>
                        <?php esc_html_e('Đơn Chờ', 'twmp-ath'); ?>
                    </a>
                </li>
                <li class="twmp-bottom-nav__item">
                    <a class="twmp-bottom-nav__link" href="<?php echo esc_url(home_url('/doanh-thu/')); ?>">
                        <svg fill="#ffffff" height="200px" width="200px" version="1.2" baseProfile="tiny" id="MO0ney_sign_by_Adioma" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 256 256" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M198.2,169.8c0-39.4-42.1-50.6-60.3-55.8c-34.4-9.6-37.3-22-36.8-28.3c1.2-15.5,18.2-19.3,34-15.9 c12.4,2.7,25.2,10,32.3,15.6L189.9,59c-11.1-7.6-25.3-17.4-46.1-21.4V12h-32.9v24.7C79,39.1,57.8,59.1,57.8,86.6 c0,26.8,19.4,39.4,38.8,48.8c16.2,7.7,61.4,15.8,58.8,36.2c-1.4,11.1-13.2,19.3-32.7,16.8c-17-2.1-35.2-16.4-35.2-16.4l-24.9,24.7 c15,12.1,30.9,19.7,48.2,23.2v24.1h32.9v-22.9C175.1,217.7,198.2,196.3,198.2,169.8z"></path> </g></svg>
                        <?php esc_html_e('Doanh thu', 'twmp-ath'); ?>
                    </a>
                </li>
                <li class="twmp-bottom-nav__item">
                    <a class="twmp-bottom-nav__link" href="#">
                        <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" fill="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M0 0h48v48H0z" fill="none"></path> <g id="Shopicon"> <path d="M40,44c2.2,0,4-1.8,4-4V4H4v36c0,2.2,1.8,4,4,4H40z M24,22c2.206,0,4-1.794,4-4v-6h4v6c0,4.411-3.589,8-8,8s-8-3.589-8-8 v-6h4v6C20,20.206,21.794,22,24,22z"></path> </g> </g></svg>
                        <?php esc_html_e('Checkout', 'twmp-ath'); ?>
                    </a>
                </li>
            </ul>
        </nav>
        <?php
    }

    add_action('wp_footer', 'twmp_cafe_menu_render_bottom_nav', 30);
}
