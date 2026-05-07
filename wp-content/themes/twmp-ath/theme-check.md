WARNING: Non-printable characters were found in the languages/vi.l10n.php file. You may want to check this file for errors.
Line 2: return ['project-id-version'=>'Twmp Phonghoa','report-msgid-bugs-to'=>'','pot-creation-date'=>'2025-06-28 09:17+0000','po-revision-date'=>'2025-08-05 14:42+0000','last-translator'=>'','language-team'=>'Tiếng Việt','language'=>'vi','plural-forms'=>'nplurals=1; plural=0;','mime-version'=>'1.0','content-type'=>'text/plain; charset=UTF-8','content-transfer-encoding'=>'8bit','x-generator'=>'Loco https://localise.biz/','x-loco-version'=>'2.8.0; wp-6.8.2; php-7.4.33','x-domain'=>'twmp-ath','messages'=>['%1$s order number %2$s'=>'%1$s Số đơn hàng %2$s','%s quantity'=>'%s số lượng','(estimated for %s)'=>'(ước tính cho %s)','(no title)'=>'(không có tiêu đề)','- Order value does not include shipping costs.'=>'- Giá trị đơn hàng được tính trước khi cộng phí vận chuyển.','404 Not Found'=>'404 Không tìm thấy','<cite class='fn'>%s</cite> <span class='says'>says:</span>'=>'<cite class='fn'>%s</cite> <span class='says'>viết:</span>','[Frontend] Logged user infoLogged in as %s.'=>'Đã đăng nhập với tư cách %s.','[Frontend] Lo
WARNING: Found echo $ in the file woocommerce/global/quantity-input.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 42: <?php echo $readonly ? 'readonly='readonly'' : ''; ?>
WARNING: Found echo $ in the file woocommerce/checkout/thankyou.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 50: <strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeO
Line 61: <strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.Escape
Line 67: <strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Securit
WARNING: Found echo $ in the file woocommerce/checkout/order-received.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 40: echo $message;
WARNING: Found echo $ in the file woocommerce/cart/cart.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 78: echo $thumbnail; // PHPCS: XSS ok.
Line 122: echo $price_html;
WARNING: Found echo $ in the file templates/core-blocks/swiper.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 44: <?php echo $item['content']; ?>
Line 47: <?php echo $item['content']; ?>
Line 69: <?php echo $slide_html; ?>
Line 72: <?php echo $slide_html; ?>
WARNING: Found echo $ in the file templates/core-blocks/image.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 35: <?php if ($data['fancybox']) : ?><a data-fancybox='<?php echo $data['fancybox_type'] ?>' data-src='<?php echo wp_get_attachment_imag
WARNING: Found echo $ in the file templates/core-blocks/heading.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 26: <?php if (!empty($data['link'])) : ?><a class='<?php echo esc_attr($_title_class); ?>' href='<?php echo $data['link'] ?>'><?php endif; ?>
Line 27: <?php echo $data['title']; ?>
Line 32: <?php echo $data['description'] ?>
WARNING: Found echo $ in the file templates/core-blocks/content-load-more.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 38: <span class='icon text-primary pe-none' aria-hidden='true'><?php echo $data['svg_icon']; ?></span>
WARNING: Found echo $ in the file templates/core-blocks/button.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 22: <span class='icon pe-none' aria-hidden='true'><?php echo $data['svg_icon_before']; ?></span>
Line 24: <span class='text pe-none'><?php echo $data['button_text']; ?></span>
Line 26: <span class='icon pe-none' aria-hidden='true'><?php echo $data['svg_icon_after']; ?></span>
Line 31: <a title='<?php echo $data['button_text'] . ' ' . $data['button_sub_text']; ?>' class='<?ph
Line 37: <?php echo $button_html; // phpcs:ignore ?>
Line 41: <?php echo $button_html; // phpcs:ignore ?>
WARNING: Found echo $ in the file templates/blocks/two-up-intro.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 45: <?php echo $data['content'] ?>
WARNING: Found echo $ in the file templates/blocks/testimonial-card.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 14: <p class='testimonial-card__title'><?php echo $data['title']; ?></p>
Line 16: <?php echo $data['description']; ?>
Line 29: <p><?php echo $data['name'] ?></p>
Line 30: <cite><?php echo $data['profession'] ?></cite>
WARNING: Found echo $ in the file templates/blocks/shop-steps.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 40: <span class='shop-steps__number'><?php echo $index + 1; ?></span>
Line 41: <span class='shop-steps__title'><?php echo $step['title']; ?></span>
WARNING: Found echo $ in the file templates/blocks/share-icon.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 16: <a class='share-icon__facebook' href='https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>'
Line 21: <a class='share-icon__twitter' href='https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>'
Line 26: <a class='share-icon__linkedin' href='https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>'
WARNING: Found echo $ in the file templates/blocks/quick-buy.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 13: echo $product->is_type('simple') ? '<input type='hidden' name='product_id' 
WARNING: Found echo $ in the file templates/blocks/quick-buy-kredivo.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 13: echo $product->is_type('simple') ? '<input type='hidden' name='product_id' 
WARNING: Found echo $ in the file templates/blocks/product-tabs.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 67: <div class='product-tabs__tab-content js-tab-content' data-category-id='<?php echo $item['category_id']; ?>' id='<?php echo $item['id']; ?>' role='tabpan
WARNING: Found echo $ in the file templates/blocks/product-tabs-slider.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 69: <div class='product-tabs__tab-content js-tab-content' data-category-id='<?php echo $item['category_id']; ?>' id='<?php echo $item['id']; ?>' role='tabpan
WARNING: Found echo $ in the file templates/blocks/product-tabs-category.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 81: <div class='product-tabs-category__tab-content js-tab-content' data-category-id='<?php echo $item['category_id']; ?>' id='<?php echo $item['id']; ?>' role='tabpan
WARNING: Found echo $ in the file templates/blocks/product-grid.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 96: <ul class='<?php echo $grid_css_class; ?>'>
Line 110: echo $product_html;
WARNING: Found echo $ in the file templates/blocks/post-meta.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 21: <span class='icon pe-none' aria-hidden='true'><?php echo $item['icon']; ?></span>
Line 22: <span class='text'><?php echo $item['text']; ?></span>
WARNING: Found echo $ in the file templates/blocks/page-title.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 17: <?php echo $data['show_form_search'] ? '<div class='col-xl-6 col-lg-6 col-md-12 c
WARNING: Found echo $ in the file templates/blocks/message-block.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 10: <?php echo $data['content']; ?>
WARNING: Found echo $ in the file templates/blocks/collection-card.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 20: <span class='collection-card__content__tag'><?php echo $data['description'] ?></span>
Line 21: <h3 class='collection-card__content__title'><?php echo $data['title'] ?></h3>
WARNING: Found echo $ in the file templates/blocks/category-grid-item.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 30: <span class='d-block category-grid__label'><span class='category-grid__label__text'><?php echo $data['term_name']; ?></span></span>
WARNING: Found echo $ in the file templates/blocks/category-card.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 47: <div class='<?php echo $_class ?>' <?php if (!empty($data['id'])) : ?> id='<?php echo esc_att
WARNING: Found echo $ in the file templates/blocks/breadcrumbs.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 6: <div class='breadcrumbs<?php echo $_class; ?>'>
Line 7: <div class='<?php echo $container; ?> breadcrumbs__container'>
WARNING: Found echo $ in the file templates/blocks/album-feedback-item.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 18: <a data-fancybox='gallery' data-src='<?php echo $image_src_data[0]; ?>'>
Line 20: <img class='image__img' width='<?php echo $image_src_data[1] ?>' height='<?php echo $image_src_data[2] ?>' src='
WARNING: Found echo $ in the file template-parts/singles/author-bio.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 13: <?php echo $author_avatar; ?>
WARNING: Found echo $ in the file template-parts/headers/icon-search.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 13: <div class='<?php echo $_class ?>' data-open-modal='modal-search-form'>
WARNING: Found echo $ in the file template-parts/headers/icon-cart.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 14: <a class='<?php echo $_class ?>' href='<?php echo $data['link'] ?>' target='_self'>
WARNING: Found echo $ in the file template-parts/footers/wcs-mini-cart.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 36: echo $thumbnail;
WARNING: Found echo $ in the file template-parts/footers/modal-search-form.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 20: <div class='<?php echo $_class; ?>' <?php echo $_attributes; ?>>
Line 36: <button class='modal__close-button <?php echo $_close_button_class; ?>' data-close-modal='modal-search-form' aria-la
WARNING: Found echo $ in the file template-parts/footers/mini-cart.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 14: <div class='<?php echo $_class ?>' data-block='mini-cart'>
WARNING: Found echo $ in the file relevanssi-live-ajax-search/search-results.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 58: echo $status_element; // phpcs:ignore WordPress.Security.EscapeOutput.Outpu
Line 79: <?php if ($product) echo $product->get_price_html(); ?>
Line 90: echo $status_element; // phpcs:ignore WordPress.Security.EscapeOutput.Outpu
WARNING: Found echo $ in the file inc/woocommerces/warranty-tracking.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 72: <td class='fullname' data-label='<?php echo esc_attr__('Customer Name:', 'twmp-ath'); ?>'><?php echo $order->get_billing_last_name(); ?></td>
Line 73: <td class='code-warranty' data-label='<?php echo esc_attr__('Warranty Code:', 'twmp-ath'); ?>'><?php echo $mabaohanh; ?></td>
Line 82: <td class='date' data-label=<?php echo esc_attr__('Received Date:', 'twmp-ath'); ?>><?php echo $warranty_string; // phpcs:ignore WordPress.Security.EscapeOutput.Outp
Line 84: <td class='status' data-label=<?php echo esc_attr__('Status:', 'twmp-ath'); ?>><?php echo $status_name; ?></td>
Line 186: <td class='date'><?php echo $warranty_string; ?></td>
WARNING: Found echo $ in the file inc/woocommerces/single.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 261: echo $price_output_html;
WARNING: Found echo $ in the file inc/woocommerces/order-tracking.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 29: <th colspan='4'><?php echo esc_html__( 'Order code', 'twmp-ath' ); ?> <?php echo $order->get_id(); ?>
Line 41: <?php echo $item->get_product()->get_image([50, 50]); ?>
Line 43: <td class='name'><?php echo $item->get_name(); ?></td>
Line 44: <td class='quantity'><?php echo $item->get_quantity(); ?></td>
Line 110: <th colspan='4'><?php echo esc_html__('Order code', 'twmp-ath'); ?> <?php echo $order->get_id(); ?>
Line 121: <td class='thumbnail'><?php echo $item->get_product()->get_image([50, 50]); ?></td>
Line 122: <td class='name'><?php echo $item->get_name(); ?></td>
Line 123: <td class='quantity'><?php echo $item->get_quantity(); ?></td>
WARNING: Found echo $ in the file inc/woocommerces/archive.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 114: echo $html;
Line 136: echo $html;
Line 158: echo $html;
Line 186: echo $html;
Line 229: echo $html;
Line 267: echo $html;
Line 279: echo $html;
WARNING: Found echo $ in the file inc/classes/class-views-theme.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 187: echo $post_views_week;
Line 191: echo $post_views;
WARNING: Found echo $ in the file inc/classes/class-breadcrumbs.php. Possible data validation issues found. All dynamic data must be correctly escaped for the context where it is rendered.
Line 152: echo $breadcrumb;
WARNING: Found ($_SERVER in the file inc/helpers/utility.php. PHP Global Variable found. Ensure the context is safe and reliable.
Line 5: return !empty($_SERVER['HTTP_X_TWMP_THEME_HEADER']) && 'development' === $_SERVER['HTTP_
WARNING: Found ($_SERVER in the file inc/classes/class-views-theme.php. PHP Global Variable found. Ensure the context is safe and reliable.
Line 83: if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
Line 84: return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
Line 85: } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
Line 86: $ips = explode(',', sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']));
Line 88: } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
Line 89: return sanitize_text_field($_SERVER['REMOTE_ADDR']);
WARNING: Found ($_SERVER in the file inc/classes/class-download-theme.php. PHP Global Variable found. Ensure the context is safe and reliable.
Line 142: $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
Line 286: $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
WARNING: Found $_SERVER in the file functions.php. PHP Global Variable found. Ensure the context is safe and reliable.
Line 169: $requested_url = $_SERVER['REQUEST_URI'];
WARNING: role="search" was found in header.php. Use get_search_form() instead of hard coding forms. Otherwise, the form can not be filtered.
WARNING: readfile was found in the file inc/classes/class-download-theme.php. File read operations should use file_get_contents() but are discouraged unless required.
Line 273: readfile($file_path);
REQUIRED: Found title="" in the file templates/blocks/post-row.php. Do not leave attributes empty.
Line 35: <a class='image__overlay-zoom post-row__overlay-link' href='<?php echo esc_url_raw(get_permalink($post_data)); ?>' title=''>
REQUIRED: Found title="" in the file templates/blocks/post-card.php. Do not leave attributes empty.
Line 34: <a class='image__overlay-zoom post-card__overlay-link' href='<?php echo esc_url_raw(get_permalink($post_data)); ?>' title=''>
REQUIRED: Found style_loader_tag in the file inc/classes/class-assets-theme.php. Do not remove core functionality.
Line 29: add_filter('style_loader_tag', [$this, 'preload_style_tag'], 10, 4);
REQUIRED: Found script_loader_tag in the file inc/classes/class-assets-theme.php. Do not remove core functionality.
Line 30: add_filter('script_loader_tag', [$this, 'add_defer_to_script'], 10, 3);
REQUIRED: The theme uses the add_shortcode() function in the file inc/woocommerces/archive.php. add_shortcode() is plugin-territory functionality and must not be used in themes. Use a plugin instead.
Line 614: add_shortcode('category_grid', 'shortcode_category_grid');