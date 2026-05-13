<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * This is the canonical normalization layer for artwork payloads.
 */
class Artwork_Data
{
    /**
     * Returns the full canonical payload shape so downstream code
     * never has to guess whether a key exists
     */
    public static function defaults(): array
    {
        return [
            'artist_id' => 0,
            'title' => '',
            'filename' => '',
            'medium' => '',
            'year' => 0,
            'dimensions' => '',
            'price' => '',
            'status' => 'available',
            'description' => '',
        ];
    }

    public static function normalize(array $input): array
    {
        // 1. Merge onto canonical defaults so every key always exists.
        $data = array_merge(self::defaults(), $input);

        // 2. Normalize each supported field explicitly.
        return [
            'artist_id' => self::normalize_artist_id($data['artist_id']),
            'title' => self::normalize_title($data['title']),
            'filename' => self::normalize_filename($data['filename']),
            'medium' => self::normalize_medium($data['medium']),
            'year' => self::normalize_year($data['year']),
            'dimensions' => self::normalize_dimensions($data['dimensions']),
            'price' => self::normalize_price($data['price']),
            'status' => self::normalize_status($data['status']),
            'description' => self::normalize_description($data['description']),
        ];
    }

    public static function normalize_artist_id($value): int
    {
        // Convert empty/invalid input to 0.
        return absint($value);
    }

    public static function normalize_title($value): string
    {
        // Coerce to string, trim, sanitize plain text.
        $value = is_string($value) ? $value : (string) $value;
        $value = trim($value);

        return sanitize_text_field($value);
    }

    public static function normalize_filename($value): string
    {
        // Coerce to string, trim, sanitize as filename.
        $value = is_string($value) ? $value : (string) $value;
        $value = trim($value);

        return sanitize_file_name($value);
    }

    public static function normalize_medium($value): string
    {
        // Keep medium as simple text.
        $value = is_string($value) ? $value : (string) $value;
        $value = trim($value);

        return sanitize_text_field($value);
    }

    public static function normalize_year($value): int
    {
        // Allow strings/numbers, empty becomes 0.
        if (is_string($value)) {
            $value = trim($value);
        }

        $year = absint($value);

        if ($year > 9999) {
            return 9999;
        }

        return $year;
    }

    public static function normalize_dimensions($value): string
    {
        // Preserve human-readable dimensions as plain text.
        $value = is_string($value) ? $value : (string) $value;
        $value = trim($value);

        return sanitize_text_field($value);
    }

    public static function normalize_price($value): string
    {
        // Preserve current plugin behavior: string output, digits/decimal only.
        $value = is_string($value) ? $value : (string) $value;
        $value = trim($value);
        /** @disregard undefined function preg_replace */
        $value = preg_replace('/[^0-9.]/', '', $value);

        return is_string($value) ? $value : '';
    }

    public static function normalize_status($value): string
    {
        // Route all status normalization through the existing helper.
        $value = is_string($value) ? $value : (string) $value;
        $value = trim($value);

        return Helpers::normalize_artwork_status($value);
    }

    public static function normalize_description($value): string
    {
        // Allow safe post content HTML for imported/manual descriptions.
        $value = is_string($value) ? $value : (string) $value;
        $value = trim($value);

        return wp_kses_post($value);
    }

    public static function build_import_key(array $normalized): string
    {
        // Caller should pass normalized data, but normalize again defensively
        // so the key remains stable if this gets called with raw input later.
        $data = self::normalize($normalized);

        $parts = [
            (string) $data['artist_id'],
            strtolower($data['filename']),
            strtolower($data['title']),
        ];

        return md5(implode('|', $parts));
    }
}
