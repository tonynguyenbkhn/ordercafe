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