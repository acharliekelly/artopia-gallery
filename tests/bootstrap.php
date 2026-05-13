<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (!function_exists('absint')) {
    function absint($maybeint): int
    {
        return abs((int) $maybeint);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value): string
    {
        $value = is_string($value) ? $value : (string) $value;
        $value = strip_tags($value);
        /** @disregard unrecognized function preg_replace */
        $value = preg_replace('/[\r\n\t ]+/', ' ', $value);

        return trim((string) $value);
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name($filename): string
    {
        $filename = is_string($filename) ? $filename : (string) $filename;
        $filename = trim($filename);
        /** @disregard unrecognized function preg_replace */
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename);
        /** @disregard unrecognized function preg_replace */
        $filename = preg_replace('/-+/', '-', $filename);

        return trim((string) $filename, '-');
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content): string
    {
        $content = is_string($content) ? $content : (string) $content;
        /** @disregard unrecognized function preg_replace */
        $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);

        return strip_tags((string) $content, '<p><br><strong><em><a>');
    }
}


$GLOBALS['artopia_test_term_meta'] = [];

if (!function_exists('get_term_meta')) {
    function get_term_meta($term_id, $key, $single = false)
    {
        $store = $GLOBALS['artopia_test_term_meta'] ?? [];

        if (!isset($store[$term_id]) || !array_key_exists($key, $store[$term_id])) {
            return $single ? '' : [];
        }

        return $store[$term_id][$key];
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($title): string
    {
        $title = is_string($title) ? $title : (string) $title;
        $title = strtolower(trim($title));
        /** @disregard unrecognized function preg_replace */
        $title = preg_replace('/[^a-z0-9]+/', '-', $title);

        return trim((string) $title, '-');
    }
}

if (!class_exists('WP_Term')) {
    class WP_Term
    {
        public int $term_id = 0;
        public string $name = '';
        public string $slug = '';

        public function __construct(object $term)
        {
            $this->term_id = isset($term->term_id) ? (int) $term->term_id : 0;
            $this->name = isset($term->name) ? (string) $term->name : '';
            $this->slug = isset($term->slug) ? (string) $term->slug : '';
        }
    }
}




require_once dirname(__DIR__) . '/includes/class-helpers.php';
require_once dirname(__DIR__) . '/includes/class-artwork-data.php';
require_once dirname(__DIR__) . '/includes/class-importer.php';
require_once dirname(__DIR__) . '/includes/class-gallery-terms.php';
