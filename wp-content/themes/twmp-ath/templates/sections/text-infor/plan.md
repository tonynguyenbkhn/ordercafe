Bước 1:

Tạo ACF field cho layout text-infor:
    - wp-content\themes\twmp-ath\acf-json\flexible_content.json
    - Bao gồm các trường
        + title: type text
        + description type wysiwyg
        + text-1: wysiwyg
        + text-2: wysiwyg
        + button primary:
            - button text
            - button link

Bước 2: 

Đăng ký layout trong wp-content\themes\twmp-ath\templates\flexible\registry.php

Bước 3: 

Tạo layout
- PHP & HTML: 
wp-content\themes\twmp-ath\templates\sections\text-infor\section.php và wp-content\themes\twmp-ath\templates\sections\text-infor\item.php. 
Cần nhúng data-block="text-infor"
- SCSS: wp-content\themes\twmp-ath\src\scss\sections\text-infor\style.scss. Hạn chế dùng grid và flex chia col, có thể dùng flex để căn chỉnh. Chia col thì dùng float và %.Nhớ clearfix
- JS: wp-content\themes\twmp-ath\src\js\blocks\text-infor.js
- Design: 
    + wp-content\themes\twmp-ath\templates\sections\text-infor\design-1.png
    + wp-content\themes\twmp-ath\templates\sections\text-infor\design-2.png

Câu hỏi: Có 2 design. Theo bạn nên làm 2 section hay gộp làm 1 để dễ maintain và fix bug.
