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
        $value = preg_replace('/[\r\n\t ]+/', ' ', $value);

        return trim((string) $value);
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name($filename): string
    {
        $filename = is_string($filename) ? $filename : (string) $filename;
        $filename = trim($filename);
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);

        return trim((string) $filename, '-');
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content): string
    {
        $content = is_string($content) ? $content : (string) $content;
        $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);

        return strip_tags((string) $content, '<p><br><strong><em><a>');
    }
}

require_once dirname(__DIR__) . '/includes/class-helpers.php';
require_once dirname(__DIR__) . '/includes/class-artwork-data.php';
require_once dirname(__DIR__) . '/includes/class-importer.php';

