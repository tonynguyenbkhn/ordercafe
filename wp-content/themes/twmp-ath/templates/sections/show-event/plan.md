Bước 1:

Tạo ACF field cho layout event:
    - wp-content\themes\twmp-ath\acf-json\flexible_content.json
    - Bao gồm các trường
        + title: type text
        + description type wysiwyg
        + button_text type text
        + button_link type url
        + select: post_object, post type là product

Bước 2: 

Đăng ký layout trong wp-content\themes\twmp-ath\templates\flexible\registry.php

Bước 3: 

Tạo layout
- PHP & HTML: wp-content\themes\twmp-ath\templates\sections\event\section.php và wp-content\themes\twmp-ath\templates\sections\event\item.php. Cần nhúng data-block="event"
- SCSS: wp-content\themes\twmp-ath\src\scss\sections\event\style.scss. Hạn chế dùng grid và flex chia col, có thể dùng flex để căn chỉnh. Chia col thì dùng float và %.Nhớ clearfix
- JS: wp-content\themes\twmp-ath\src\js\blocks\event.js
- Design: wp-content\themes\twmp-ath\templates\sections\event\design\image.png

- Sử dụng swiper
    + slidesPerView giống team/section.php
    + navigation giống team/section.php
    + pagination: yes
    
- Item
    Mặc định:
    + wp-content\themes\twmp-ath\acf-json\service_acf.json
        - field_ath_event_badges
        - field_ath_short_info
        - field_ath_location
    + Hiển thị ngày tháng
    Khi Hover:
    + Dưới field_ath_location 
        - hiển thị thêm mô tả
        - button book ticket
        - Button view more

Trao đổi

1. Về ACF & dữ liệu
Field post_object (product):
Cho phép chọn multiple hay single? => multiple
Có cần filter theo taxonomy (ví dụ category event) không? => Không
Các field dùng trong item:
field_ath_event_badges → dạng gì? (repeater / select / text?)
field_ath_short_info → text hay wysiwyg?
field_ath_location → text đơn hay có link/map?
=> check trong file: wp-content\themes\twmp-ath\acf-json\service_acf.json
Ngày tháng:
Lấy từ đâu? (product có field riêng hay dùng post date?) => Ngày tạo sản phẩm
Format mong muốn? (VD: 13 SAT MAY,26 như design?) => OK
2. Về UI/UX (rất quan trọng vì swiper + hover)
Slider:
slidesPerView giống team → confirm: => Đúng
desktop / tablet / mobile cụ thể là bao nhiêu? => trong file team/section.php đã có
Pagination:
dạng bullets hay progressbar? => progressbar nếu có thể
Hover:
Áp dụng desktop only đúng không? Mobile xử lý thế nào? => Áp dụng cả desktop và mobile
Animation:
Hover có cần fade/slide effect không hay chỉ show/hide? => Có fade
3. Về Button
button_text + button_link (section level):
Là nút “All Show & Event” đúng không? => Đúng
Trong item:
Book Ticket → link lấy từ đâu? (product link hay field riêng?) => là function twmp_render_cart_button, check trong wp-content\themes\twmp-ath\inc\woocommerces\single.php
View Detail → link về product detail? => Đúng
4. Về design / layout
Card đầu tiên (màu đỏ trong ảnh):
Có phải highlight item (featured) hay tất cả giống nhau? => là highlight
Badge “Available”:
Logic lấy từ đâu? (stock status của Woo?) => Không lấy stock status mà lấy từ field_ath_event_badges
Image:
Dùng product thumbnail hay field custom? => ảnh đại diện của sản phẩm
5. Về code structure (theme conventions)
Theme đang dùng:
Có helper nào sẵn không? (VD: render_image, render_button…) => Sử dụng element trong components/
Swiper:
Đã enqueue global hay cần import riêng trong block? => đã nhúng vào theme rồi
SCSS:
Có biến/mixin chung không (color, breakpoint)?
6. Về performance
Có cần:
Lazy load image? => có
Limit số product (VD: 6–10 items)? => 6
Cache query? => Không
7. Edge cases
Nếu không có product nào → hide section hay show empty? => show empty
Nếu thiếu field (location, badge…) → fallback UI? => không hiển thị