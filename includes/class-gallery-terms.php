<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
    exit;
}

class Gallery_Terms
{
    private const ARTIST_META_KEY = '_artopia_artist_id';

    public static function artist_meta_key(): string
    {
        return self::ARTIST_META_KEY;
    }

    public function get_artist_id_for_term(int $term_id): int
    {
        $artist_id = get_term_meta($term_id, self::ARTIST_META_KEY, true);

        return absint($artist_id);
    }

    public function term_belongs_to_artist(int $term_id, int $artist_id): bool
    {
        $artist_id = absint($artist_id);

        if ($term_id <= 0 || $artist_id <= 0) {
            return false;
        }

        return $this->get_artist_id_for_term($term_id) === $artist_id;
    }

    public function term_matches_artist_and_name(\WP_Term $term, int $artist_id, string $gallery_name): bool
    {
        if (!$this->term_belongs_to_artist((int) $term->term_id, $artist_id)) {
            return false;
        }

        $requested = strtolower(trim(sanitize_text_field($gallery_name)));
        $actual = strtolower(trim($term->name));

        return $requested !== '' && $requested === $actual;
    }

    public function term_matches_artist_and_slug(\WP_Term $term, int $artist_id, string $gallery_slug): bool
    {
        if (!$this->term_belongs_to_artist((int) $term->term_id, $artist_id)) {
            return false;
        }

        $requested = sanitize_title($gallery_slug);
        $actual = isset($term->slug) ? (string) $term->slug : '';

        return $requested !== '' && $requested === $actual;
    }

    public function find_by_artist_and_name(int $artist_id, string $gallery_name): ?\WP_Term
    {
        $artist_id = absint($artist_id);
        $gallery_name = trim(sanitize_text_field($gallery_name));

        if ($artist_id <= 0 || $gallery_name === '') {
            return null;
        }

        $terms = get_terms([
            'taxonomy' => 'gallery',
            'hide_empty' => false,
            'name' => $gallery_name,
        ]);

        if (is_wp_error($terms) || empty($terms) || !is_array($terms)) {
            return null;
        }

        foreach ($terms as $term) {
            if ($term instanceof \WP_Term && $this->term_matches_artist_and_name($term, $artist_id, $gallery_name)) {
                return $term;
            }
        }

        return null;
    }

    public function find_by_artist_and_slug(int $artist_id, string $gallery_slug): ?\WP_Term
    {
        $artist_id = absint($artist_id);
        $gallery_slug = sanitize_title($gallery_slug);

        if ($artist_id <= 0 || $gallery_slug === '') {
            return null;
        }

        $terms = get_terms([
            'taxonomy' => 'gallery',
            'hide_empty' => false,
            'slug' => $gallery_slug,
        ]);

        if (is_wp_error($terms) || empty($terms) || !is_array($terms)) {
            return null;
        }

        foreach ($terms as $term) {
            if ($term instanceof \WP_Term && $this->term_matches_artist_and_slug($term, $artist_id, $gallery_slug)) {
                return $term;
            }
        }

        return null;
    }

    /**
     * @return int|\WP_Error
     */
    public function get_or_create_for_artist(int $artist_id, string $gallery_name)
    {
        $artist_id = absint($artist_id);
        $gallery_name = trim(sanitize_text_field($gallery_name));

        if ($artist_id <= 0) {
            return new \WP_Error(
                'artopia_invalid_artist',
                __('A valid artist is required to create or find a gallery.', 'artopia-gallery')
            );
        }

        if ($gallery_name === '') {
            return new \WP_Error(
                'artopia_invalid_gallery_name',
                __('A gallery name is required.', 'artopia-gallery')
            );
        }

        $existing = $this->find_by_artist_and_name($artist_id, $gallery_name);

        if ($existing instanceof \WP_Term) {
            return (int) $existing->term_id;
        }

        $created = wp_insert_term($gallery_name, 'gallery');

        if (is_wp_error($created)) {
            return $created;
        }

        $term_id = isset($created['term_id']) ? (int) $created['term_id'] : 0;

        if ($term_id <= 0) {
            return new \WP_Error(
                'artopia_invalid_gallery_term',
                __('Gallery term creation did not return a valid term ID.', 'artopia-gallery')
            );
        }

        update_term_meta($term_id, self::ARTIST_META_KEY, $artist_id);

        return $term_id;
    }
}
