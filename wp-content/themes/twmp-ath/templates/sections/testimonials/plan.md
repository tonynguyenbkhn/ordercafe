Bước 1:

Tạo ACF field cho layout testimonials:
    - wp-content\themes\twmp-ath\acf-json\flexible_content.json
    - Bao gồm các trường
        + title: type text
        + description type wysiwyg
        + repeat: 
            - avatar
            - name
            - school
            - content

Bước 2: 

Đăng ký layout trong wp-content\themes\twmp-ath\templates\flexible\registry.php

Bước 3: 

Tạo layout
- PHP & HTML: 
wp-content\themes\twmp-ath\templates\sections\testimonials\section.php và wp-content\themes\twmp-ath\templates\sections\testimonials\item.php. 
Cần nhúng data-block="testimonials"
- SCSS: wp-content\themes\twmp-ath\src\scss\sections\testimonials\style.scss. Hạn chế dùng grid và flex chia col, có thể dùng flex để căn chỉnh. Chia col thì dùng float và %.Nhớ clearfix
- JS: wp-content\themes\twmp-ath\src\js\blocks\testimonials.js
- Design: wp-content\themes\twmp-ath\templates\sections\testimonials\design.png
- Sử dụng swiper
    + slidesPerView
    + navigation: false
    + pagination: tru, type progressbar
    + centre mode = true