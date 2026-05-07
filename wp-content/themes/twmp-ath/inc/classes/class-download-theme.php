<?php

namespace TWMP_THEME\Inc;

use TWMP_THEME\Inc\Traits\Singleton;

class Download_Theme
{

    use Singleton;

    protected function __construct()
    {

        // load class.
        $this->setup_hooks();
    }

    protected function setup_hooks()
    {

        /**
         * Actions.
         */
        add_action('acf/save_post', [$this, 'twmp_generate_download_counter_random_hash'], 20);
        add_action('init',          [$this, 'twmp_download_counter_add_rewrite'], 0);
        add_action('parse_request', [$this, 'twmp_download_counter_download_handler']);
    }

    function simple_download_counter_download_vars($id, $array)
    {
        list($download_title, $download_url, $download_count, $download_version, $download_type, $download_ext, $download_size) = $this->simple_download_counter_props($id);

        foreach ($array as $key => $value) {

            if ($key === 'text' || $key === 'title') {

                $array[$key] = str_replace('%id%',      $id,                            $array[$key]);
                $array[$key] = str_replace('%title%',   $download_title,                $array[$key]);
                $array[$key] = str_replace('%version%', $download_version,              $array[$key]);
                $array[$key] = str_replace('%type%',    ucfirst($download_type),        $array[$key]);
                $array[$key] = str_replace('%ext%',     strtoupper($download_ext),      $array[$key]);
                $array[$key] = str_replace('%count%',   number_format($download_count), $array[$key]);
                $array[$key] = str_replace('%size%',    $download_size,                 $array[$key]);
            } else {

                $array[$key] = str_replace('%id%',      '<span class="taiwebmienphi-id">' .      $id                            . '</span>', $array[$key]);
                $array[$key] = str_replace('%title%',   '<span class="taiwebmienphi-title">' .   $download_title                . '</span>', $array[$key]);
                $array[$key] = str_replace('%version%', '<span class="taiwebmienphi-version">' . $download_version              . '</span>', $array[$key]);
                $array[$key] = str_replace('%type%',    '<span class="taiwebmienphi-type">' .    ucfirst($download_type)        . '</span>', $array[$key]);
                $array[$key] = str_replace('%ext%',     '<span class="taiwebmienphi-ext">' .     strtoupper($download_ext)      . '</span>', $array[$key]);
                $array[$key] = str_replace('%count%',   '<span class="taiwebmienphi-count">' .   number_format($download_count) . '</span>', $array[$key]);
                $array[$key] = str_replace('%size%',    '<span class="taiwebmienphi-size">' .    $download_size                 . '</span>', $array[$key]);
            }
        }

        $text   = isset($array['text'])   ? $array['text']  : '';

        $title  = isset($array['title'])  ? $array['title'] : '';

        $before = (isset($array['before']) && !empty($array['before'])) ? '<span class="taiwebmienphi-before">' . $array['before'] . '</span>' : '';

        $after  = (isset($array['after'])  && !empty($array['after']))  ? '<span class="taiwebmienphi-after">' .  $array['after']  . '</span>' : '';

        return array($array, $text, $title, $before, $after, $download_url);
    }

    function simple_download_counter_props($id)
    {

        $download = get_post(absint($id));

        if (empty($download)) return false;

        $download_title = $download->post_title;

        $download_url     = $this->simple_download_counter_download_url($id);
        $download_count   = get_post_meta($id, 'theme_download_count', true) ? get_post_meta($id, 'theme_download_count', true) : 0;
        $download_version = get_post_meta($id, 'theme_download_version', true);
        $download_type    = 'undefined';
        $download_ext     = get_post_meta($id, 'theme_download_note', true) ? get_post_meta($id, 'theme_download_note', true) : '';
        $download_size    = $this->simple_download_counter_format_size(get_post_meta($id, 'theme_download_size', true));

        return array($download_title, $download_url, $download_count, $download_version, $download_type, $download_ext, $download_size);
    }

    function simple_download_counter_download_url($id)
    {
        // return http or https
        $scheme = parse_url(home_url(), PHP_URL_SCHEME);

        $key = $this->simple_download_counter_key();

        if (get_option('permalink_structure')) {

            $url = home_url('/' . $key . '/' . $id . '/', $scheme);
        } else {

            $url = add_query_arg($key, $id, home_url('', $scheme));
        }

        $hash = get_post_meta($id, 'them_download_hash', true);

        if ($hash) {

            $url = add_query_arg('key', $hash, $url);
        }

        return apply_filters('simple_download_counter_get_url', esc_url_raw($url));
    }

    function simple_download_counter_key()
    {

        return 'twmp_download';
    }

    function twmp_generate_download_counter_random_hash($post_id)
    {
        if ($post_id === 'options') return;

        $post_type = get_post_type($post_id);
        if ($post_type !== 'post') return;

        $hash = get_post_meta($post_id, 'them_download_hash', true);

        if (!$hash) {

            $hash = $this->simple_download_counter_random_hash(30);

            update_post_meta($post_id, 'them_download_hash', $hash);
        }

        $file_url = get_post_meta($post_id, 'theme_download_url', true);
        $file_path = $file_url;
        $parsed = parse_url($file_path);
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : null;
        $path   = isset($parsed['path'])   ? $parsed['path']   : null;
        $host   = isset($parsed['host'])   ? $parsed['host']   : null;
        $domain   = $scheme . '://' . $host;
        $domain   = filter_var($domain, FILTER_VALIDATE_URL) ? esc_url($domain) : null;
        $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
        $wp_uploads     = wp_upload_dir();
        $wp_uploads_dir = isset($wp_uploads['basedir']) ? $wp_uploads['basedir'] : null;
        $wp_uploads_url = isset($wp_uploads['baseurl']) ? $wp_uploads['baseurl'] : null;

        $wp_uploads_host = parse_url($wp_uploads_url);
        $wp_uploads_host = isset($wp_uploads_host['host']) ? $wp_uploads_host['host'] : null;

        $file_path = str_replace($wp_uploads_url, $wp_uploads_dir, $file_path);

        $file_path = realpath($file_path);

        $file_path = wp_normalize_path($file_path);

        $file_path = (empty($file_path) || !is_file($file_path)) ? new \WP_Error('file_does_not_exist', __('File does not exist. Error code: ', 'twmp-ath') . 3) : $file_path;

        $file_url  = is_wp_error($file_path) ? $file_path : $file_url;
        if ( !$file_path->errors ) {
            update_post_meta($post_id, 'theme_download_path', $file_path);
        }
    }

    private function simple_download_counter_format_size($bytes)
    {

        if ($bytes >= 1073741824) {

            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {

            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {

            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {

            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {

            $bytes = $bytes . ' byte';
        } else {

            $bytes = 'Unknown size';
        }

        return $bytes;
    }

    private function simple_download_counter_random_hash($length = 10)
    {

        $key = '';

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';

        $characters_length = strlen($characters);

        for ($i = 0; $i < $length; $i++) {

            $key .= $characters[rand(0, $characters_length - 1)];
        }

        return $key;
    }

    function twmp_download_counter_download_handler()
    {
        global $wp;

        list($url, $text) = $this->simple_download_counter_current_page();

        $current = ' <a href="' . esc_url($url) . '">' . esc_html($text) . '</a>';

        $title = __('Download Error', 'twmp-ath');

        $key = 'twmp_download';

        if (isset($_GET[$key]) && !empty($_GET[$key])) {
            $wp->query_vars[$key] = sanitize_key($_GET[$key]);
        }

        if (isset($wp->query_vars[$key]) && !empty($wp->query_vars[$key])) {

            if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);

            $id = sanitize_title(wp_unslash($wp->query_vars[$key]));

            if (empty($id)) {
                wp_die(__('Download ID is undefined.', 'twmp-ath') . $current, $title);
            }

            $hash = get_post_meta($id, 'them_download_hash', true);

            if ($hash) {

                $hash_key = (isset($_GET['key']) && !empty($_GET['key'])) ? $_GET['key'] : '';

                if (!$hash_key || $hash_key !== $hash) {
                    wp_die(__('Download hash does not match.', 'twmp-ath') . $current, $title);
                }
            }

            if (get_post_status($id) !== 'publish') {
                wp_die(__('Download is not available.', 'twmp-ath') . $current, $title);
            }

            $file_url = get_post_meta($id, 'theme_download_url', true);

            if (empty($file_url)) {

                wp_die(__('File URL is not defined.', 'twmp-ath') . $current, $title);
            }

            $file_path = get_post_meta($id, 'theme_download_path', true);

            $count = get_post_meta($id, 'theme_download_count', true);

            update_post_meta($id, 'theme_download_count', absint($count) + 1);
            $file_name = wp_basename(parse_url($file_path, PHP_URL_PATH));
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'. $file_name .'"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length:'. filesize($file_path));
            header('Connection: Keep-Alive');
            header('Expires: 0');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            
            while (ob_get_level()) ob_end_clean();
            
            readfile($file_path);
            
            exit;
        }
    }

    function simple_download_counter_current_page()
    {

        $url = home_url();

        $text = __('Go to homepage &raquo;', 'twmp-ath');

        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

        if (!empty($referrer)) {

            if (filter_var($referrer, FILTER_VALIDATE_URL)) {

                $url = $referrer;

                $text = __('Return to previous page &#10550;', 'twmp-ath');
            }
        }

        return array($url, $text);
    }

    function twmp_download_counter_add_rewrite()
    {
        add_rewrite_endpoint('twmp_download', EP_ALL);
    }
}
