Dưới đây là kết quả review theme twmp-phonghoa:

1. LỖI BẢO MẬT (Security Issues)
1.1 XSS - Thiếu escaping trong output HTML
header.php:37 - Preload style tag dùng $href trực tiếp mà không escape:


// class-assets-theme.php:37 - NGUY HIỂM
$html = "<link rel='preload' as='style' href='{$href}' ...>";
// Sửa thành:
$html = "<link rel='preload' as='style' href='" . esc_url($href) . "' ...>";
class-assets-theme.php:48 - Script tag cũng không escape $src:


return "<script src='{$src}' defer></script>";
// Sửa thành:
return "<script src='" . esc_url($src) . "' defer></script>";
1.2 AJAX handlers thiếu sanitization
checkout.php:180 - $_POST['matp'] và $_POST['maqh'] không được sanitize:


$matp = $_POST['matp']; // NGUY HIỂM
// Sửa thành:
$matp = sanitize_text_field($_POST['matp']);
1.3 AJAX handlers thiếu nonce verification ✅ FIXED
Các hàm load_tinh_tp_ajax(), load_quan_huyen_ajax(), load_xa_phuong_ajax() tại checkout.php - Thêm:
- wp_verify_nonce() để xác thực security token
- Truyền nonce qua data-settings
- Validate $_POST parameters (check empty, sanitize)
- Proper error responses (400, 403)

**Frontend updates:**
- asl-custom.js: Thêm nonce vào get_tinh_tp_by_matp & get_quan_huyen_by_matp AJAX calls
- checkout.js: Helper function twmpLoadXaPhuong(maqh, selectedWard, selector) với nonce support

1.4 REST API thiếu rate limiting & validation ✅ FIXED
class-views-theme.php - Thêm:
- twmp_check_post_views_permission() - rate limit 10 requests/min per IP
- twmp_get_client_ip() - get real client IP (support proxy)
- Validate post_id exists & is published
- Proper error responses (404, 403, 429)

1.5 json_encode không escaped trong HTML attribute
checkout.php:31:


echo '<div ... data-settings='.json_encode($block_attributes).' ...>';
// Sửa thành:
echo '<div ... data-settings="' . esc_attr(json_encode($block_attributes)) . '" ...>';
2. LỖI CODE (Bugs)
2.1 HTML tag không đóng
class-rest-api-theme.php:107 - Thiếu > đóng tag:


echo '</div';  // Thiếu >
// Sửa: 
echo '</div>';
2.2 GTM duplicate tracking
header.php load cả GTM (line 14-28) VÀ gtag.js (line 113-124). Đây là 2 hệ thống tracking khác nhau (GTM-W885V6JK và G-M191XPHW65) nhưng có thể gây double-counting nếu GTM container cũng chứa GA4 tag.

2.3 Commented-out code quá nhiều
Theme có rất nhiều code commented-out trải khắp các file, gây khó đọc và bảo trì. Nên dọn dẹp.

3. HIỆU NĂNG (Performance Issues)
3.1 Google Fonts blocking render
header.php:9-11 - Google Fonts load đồng bộ, block rendering. Nên dùng font-display: swap và preload, hoặc self-host font:


<!-- Thêm &display=swap nếu chưa có (đã có rồi, tốt) -->
<!-- Nhưng nên dùng preload: -->
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter...&display=swap" 
      onload="this.onload=null;this.rel='stylesheet'">
3.2 Critical CSS inline quá lớn
class-assets-theme.php:54-71 - file_get_contents đọc toàn bộ bootstrap.min.css + critical_frontend.min.css rồi inline vào <head>. Bootstrap CSS rất lớn - chỉ nên inline phần critical thật sự cần, không phải toàn bộ Bootstrap.

3.3 Font files quá nhiều
Theme chứa 42 file font Roboto (Regular, Condensed, SemiCondensed, tất cả weight). Hầu hết không cần thiết. Chỉ nên giữ 3-4 weight thật sự dùng.

3.4 jQuery dependency
Cả frontend.min.js và woocommerce.min.js đều phụ thuộc jQuery. Nên migrate sang vanilla JS để giảm payload.

3.5 Scripts load trên mọi trang
class-assets-theme.php:84-85 - shop.js và checkout.js load trên MỌI trang:


// Nên chỉ load khi cần:
if (is_shop() || is_product_category()) {
    wp_enqueue_script('twmp-woocommerce-shop', ...);
}
if (is_checkout()) {
    wp_enqueue_script('twmp-woocommerce-checkout', ...);
}
3.6 Footer inline scripts không minified
footer.php:105-137 - 2 đoạn inline script nên chuyển vào file JS riêng và chỉ enqueue khi cần (is_product()).

3.7 helper.php quá lớn
File helper.php nặng 924KB - file PHP không nên lớn thế. Có thể chứa data cities hardcode? Nên lưu vào database hoặc file JSON riêng và chỉ load khi cần.

4. CODE QUALITY
4.1 SVG icons inline trong PHP
template-functions.php chứa switch/case khổng lồ với SVG inline. Nên chuyển sang file SVG riêng hoặc dùng SVG sprite:


function twmp_get_svg_icon($name) {
    $path = get_theme_file_path("assets/icons/{$name}.svg");
    if (file_exists($path)) {
        return file_get_contents($path);
    }
    return '';
}
4.2 var thay vì visibility keywords
class-assets-theme.php:11-12:


var $theme_version;  // Nên dùng: protected $theme_version;
var $theme_env;      // Nên dùng: protected $theme_env;
4.3 Admin CSS output trong loop
class-views-theme.php:123-126 - <style> tag output trong twmp_columns_content() - mỗi row đều output CSS. Nên dùng admin_enqueue_scripts thay vào.

4.4 MutationObserver monkey-patching
global.php:107-120 - Override MutationObserver.prototype.observe trong admin footer là hack nguy hiểm, có thể gây side effect không mong muốn. Nên fix root cause.

5. ĐỀ XUẤT TỐI ƯU
Ưu tiên	Hành động	Tác động
Cao	Fix XSS: escape $href, $src, $_POST values	Bảo mật
Cao	Fix </div thiếu > trong rest-api-theme.php	Bug
Cao	Thêm nonce check cho AJAX handlers	Bảo mật
Cao	Chỉ load shop.js/checkout.js trên trang cần	Performance
TB	Giảm font files từ 42 xuống 4-6	-500KB+
TB	Tách helper.php (924KB) - chuyển data cities sang JSON	Memory
TB	Self-host Google Fonts hoặc preload	LCP
TB	Chỉ inline critical CSS thực sự, không inline cả Bootstrap	TTFB
Thấp	Chuyển SVG switch/case sang file riêng	Maintainability
Thấp	Dọn commented-out code	Code quality
Thấp	Migrate jQuery sang vanilla JS	Bundle size
Bạn muốn tôi bắt tay fix vấn đề nào trước?