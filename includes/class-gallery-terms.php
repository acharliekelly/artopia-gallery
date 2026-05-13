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
}
