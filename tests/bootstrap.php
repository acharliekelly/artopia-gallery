<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

$GLOBALS['artopia_test_term_meta'] = [];
$GLOBALS['artopia_test_terms'] = [];
$GLOBALS['artopia_test_next_term_id'] = 1000;


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

if (!function_exists('update_term_meta')) {
    function update_term_meta($term_id, $key, $value): bool
    {
        if (!isset($GLOBALS['artopia_test_term_meta'][$term_id])) {
            $GLOBALS['artopia_test_term_meta'][$term_id] = [];
        }

        $GLOBALS['artopia_test_term_meta'][$term_id][$key] = $value;

        return true;
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


if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private string $code;
        private string $message;

        public function __construct(string $code = '', string $message = '')
        {
            $this->code = $code;
            $this->message = $message;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool
    {
        return $thing instanceof \WP_Error;
    }
}

if (!function_exists('get_terms')) {
    function get_terms(array $args = [])
    {
        $terms = $GLOBALS['artopia_test_terms'] ?? [];

        if (isset($args['taxonomy']) && $args['taxonomy'] !== 'gallery') {
            return [];
        }

        $filtered = [];

        foreach ($terms as $term) {
            if (!$term instanceof \WP_Term) {
                continue;
            }

            if (isset($args['name']) && (string) $args['name'] !== '' && $term->name !== (string) $args['name']) {
                continue;
            }

            if (isset($args['slug']) && (string) $args['slug'] !== '' && $term->slug !== (string) $args['slug']) {
                continue;
            }

            $filtered[] = $term;
        }

        return array_values($filtered);
    }
}

if (!function_exists('wp_insert_term')) {
    function wp_insert_term($term, $taxonomy)
    {
        if ($taxonomy !== 'gallery') {
            return new \WP_Error('invalid_taxonomy', 'Invalid taxonomy.');
        }

        $name = is_string($term) ? trim($term) : (string) $term;

        if ($name === '') {
            return new \WP_Error('empty_term_name', 'Term name is required.');
        }

        $term_id = (int) ($GLOBALS['artopia_test_next_term_id'] ?? 1000);
        $GLOBALS['artopia_test_next_term_id'] = $term_id + 1;

        $slug = sanitize_title($name);

        $wpTerm = new \WP_Term((object) [
            'term_id' => $term_id,
            'name' => $name,
            'slug' => $slug,
        ]);

        $GLOBALS['artopia_test_terms'][] = $wpTerm;

        return [
            'term_id' => $term_id,
            'term_taxonomy_id' => $term_id,
        ];
    }
}

if (!function_exists('artopia_reset_test_term_store')) {
    function artopia_reset_test_term_store(): void
    {
        $GLOBALS['artopia_test_term_meta'] = [];
        $GLOBALS['artopia_test_terms'] = [];
        $GLOBALS['artopia_test_next_term_id'] = 1000;
    }
}






require_once dirname(__DIR__) . '/includes/class-helpers.php';
require_once dirname(__DIR__) . '/includes/class-artwork-data.php';
require_once dirname(__DIR__) . '/includes/class-importer.php';
require_once dirname(__DIR__) . '/includes/class-gallery-terms.php';
