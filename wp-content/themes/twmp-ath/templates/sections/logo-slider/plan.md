Bước 1:

Tạo ACF field cho layout logo-slider:
    - wp-content\themes\twmp-ath\acf-json\flexible_content.json
    - Bao gồm các trường
        + title: type text
        + description type wysiwyg
        + gallery: Cho chọn nhiều hình ảnh, value là mảng id

Bước 2: 

Đăng ký layout trong wp-content\themes\twmp-ath\templates\flexible\registry.php

Bước 3: 

Tạo layout
- PHP & HTML: wp-content\themes\twmp-ath\templates\sections\logo-slider\section.php và wp-content\themes\twmp-ath\templates\sections\logo-slider\item.php. Cần nhúng data-block="logo-slider"
- SCSS: wp-content\themes\twmp-ath\src\scss\sections\logo-slider\style.scss. Hạn chế dùng grid và flex chia col, có thể dùng flex để căn chỉnh. Chia col thì dùng float và %.Nhớ clearfix
- JS: wp-content\themes\twmp-ath\src\js\blocks\logo-slider.js

- Sử dụng swiper
    + slidesPerView
    + navigation: false
    + pagination: false