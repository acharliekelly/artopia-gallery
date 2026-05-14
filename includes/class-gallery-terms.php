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

        $legacy = $this->find_legacy_unowned_by_name($gallery_name);

        if ($legacy instanceof \WP_Term) {
            update_term_meta((int) $legacy->term_id, self::ARTIST_META_KEY, $artist_id);

            return (int) $legacy->term_id;
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

    public function term_is_unowned(int $term_id): bool
    {
        return $this->get_artist_id_for_term($term_id) === 0;
    }

    public function find_legacy_unowned_by_name(string $gallery_name): ?\WP_Term
    {
        $gallery_name = trim(sanitize_text_field($gallery_name));

        if ($gallery_name === '') {
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
            if (!$term instanceof \WP_Term) {
                continue;
            }

            if ($this->term_is_unowned((int) $term->term_id)) {
                return $term;
            }
        }

        return null;
    }

    public function find_legacy_unowned_by_slug(string $gallery_slug): ?\WP_Term
    {
        $gallery_slug = sanitize_title($gallery_slug);

        if ($gallery_slug === '') {
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
            if (!$term instanceof \WP_Term) {
                continue;
            }

            if ($this->term_is_unowned((int) $term->term_id)) {
                return $term;
            }
        }

        return null;
    }

    public function find_by_artist_and_name_with_legacy_fallback(int $artist_id, string $gallery_name): ?\WP_Term
    {
        $owned = $this->find_by_artist_and_name($artist_id, $gallery_name);

        if ($owned instanceof \WP_Term) {
            return $owned;
        }

        return $this->find_legacy_unowned_by_name($gallery_name);
    }

    public function find_by_artist_and_slug_with_legacy_fallback(int $artist_id, string $gallery_slug): ?\WP_Term
    {
        $owned = $this->find_by_artist_and_slug($artist_id, $gallery_slug);

        if ($owned instanceof \WP_Term) {
            return $owned;
        }

        return $this->find_legacy_unowned_by_slug($gallery_slug);
    }

    public function get_gallery_ownership_report(): array
    {
        $report = [
            'summary' => [
                'total' => 0,
                'owned' => 0,
                'unowned' => 0,
            ],
            'rows' => [],
        ];

        $terms = get_terms([
            'taxonomy' => 'gallery',
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms) || !is_array($terms)) {
            return $report;
        }

        foreach ($terms as $term) {
            if (!$term instanceof \WP_Term) {
                continue;
            }

            $artist_id = $this->get_artist_id_for_term((int) $term->term_id);
            $artist_name = '';
            $is_owned = $artist_id > 0;

            if ($is_owned) {
                $artist = get_post($artist_id);

                if ($artist && $artist->post_type === 'artist') {
                    $artist_name = (string) $artist->post_title;
                }
            }

            $report['rows'][] = [
                'term_id' => (int) $term->term_id,
                'name' => (string) $term->name,
                'slug' => (string) $term->slug,
                'artist_id' => $artist_id,
                'artist_name' => $artist_name,
                'is_owned' => $is_owned,
            ];

            $report['summary']['total']++;

            if ($is_owned) {
                $report['summary']['owned']++;
            } else {
                $report['summary']['unowned']++;
            }
        }

        return $report;
    }

    /**
     * @return true|\WP_Error
     */
    public function assign_artist_to_term(int $term_id, int $artist_id)
    {
        $term_id = absint($term_id);
        $artist_id = absint($artist_id);

        if ($term_id <= 0) {
            return new \WP_Error(
                'artopia_invalid_term',
                __('A valid gallery term is required.', 'artopia-gallery')
            );
        }

        if ($artist_id <= 0) {
            return new \WP_Error(
                'artopia_invalid_artist',
                __('A valid artist is required.', 'artopia-gallery')
            );
        }

        $term = get_term($term_id, 'gallery');

        if (!$term || is_wp_error($term) || !($term instanceof \WP_Term)) {
            return new \WP_Error(
                'artopia_term_not_found',
                __('Gallery term not found.', 'artopia-gallery')
            );
        }

        if ($term->taxonomy !== 'gallery') {
            return new \WP_Error(
                'artopia_invalid_taxonomy',
                __('Selected term is not a gallery term.', 'artopia-gallery')
            );
        }

        if (!$this->term_is_unowned($term_id)) {
            return new \WP_Error(
                'artopia_term_already_owned',
                __('This gallery term already has an assigned artist owner.', 'artopia-gallery')
            );
        }

        $artist = get_post($artist_id);

        if (!$artist || $artist->post_type !== 'artist') {
            return new \WP_Error(
                'artopia_artist_not_found',
                __('Artist not found.', 'artopia-gallery')
            );
        }

        update_term_meta($term_id, self::ARTIST_META_KEY, $artist_id);

        return true;
    }

    public function infer_artist_id_for_legacy_term(int $term_id): int
    {
        $term_id = absint($term_id);

        if ($term_id <= 0 || !$this->term_is_unowned($term_id)) {
            return 0;
        }

        $artworks = $this->get_artwork_ids_for_gallery_term($term_id);

        if (!is_array($artworks) || empty($artworks)) {
            return 0;
        }

        $artist_ids = [];

        foreach ($artworks as $artwork_id) {
            $artist_id = absint(get_post_meta((int) $artwork_id, '_artopia_artist_id', true));

            if ($artist_id > 0) {
                $artist_ids[$artist_id] = true;
            }
        }

        $distinct_artist_ids = array_keys($artist_ids);

        if (count($distinct_artist_ids) !== 1) {
            return 0;
        }

        return (int) $distinct_artist_ids[0];
    }

    public function backfill_legacy_gallery_ownership(): array
    {
        $result = [
            'updated' => 0,
            'skipped' => 0,
            'ambiguous' => 0,
            'updated_terms' => [],
            'skipped_terms' => [],
            'ambiguous_terms' => [],
        ];

        $terms = get_terms([
            'taxonomy' => 'gallery',
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms) || !is_array($terms)) {
            return $result;
        }

        foreach ($terms as $term) {
            if (!$term instanceof \WP_Term) {
                continue;
            }

            $term_id = (int) $term->term_id;

            if (!$this->term_is_unowned($term_id)) {
                continue;
            }

            $artworks = $this->get_artwork_ids_for_gallery_term($term_id);

            if (!is_array($artworks) || empty($artworks)) {
                $result['skipped']++;
                $result['skipped_terms'][] = [
                    'term_id' => $term_id,
                    'name' => (string) $term->name,
                    'reason' => __('No artworks are attached to this gallery term.', 'artopia-gallery'),
                ];
                continue;
            }

            $artist_ids = [];

            foreach ($artworks as $artwork_id) {
                $artist_id = absint(get_post_meta((int) $artwork_id, '_artopia_artist_id', true));

                if ($artist_id > 0) {
                    $artist_ids[$artist_id] = true;
                }
            }

            $distinct_artist_ids = array_keys($artist_ids);

            if (count($distinct_artist_ids) === 1) {
                $artist_id = (int) $distinct_artist_ids[0];
                update_term_meta($term_id, self::ARTIST_META_KEY, $artist_id);

                $result['updated']++;
                $result['updated_terms'][] = [
                    'term_id' => $term_id,
                    'name' => (string) $term->name,
                    'artist_id' => $artist_id,
                ];
                continue;
            }

            if (count($distinct_artist_ids) > 1) {
                $result['ambiguous']++;
                $result['ambiguous_terms'][] = [
                    'term_id' => $term_id,
                    'name' => (string) $term->name,
                    'reason' => __('Attached artworks reference multiple artists.', 'artopia-gallery'),
                ];
                continue;
            }

            $result['skipped']++;
            $result['skipped_terms'][] = [
                'term_id' => $term_id,
                'name' => (string) $term->name,
                'reason' => __('Attached artworks do not provide a usable artist ID.', 'artopia-gallery'),
            ];
        }

        return $result;
    }

    private function get_artwork_ids_for_gallery_term(int $term_id): array
    {
        $artworks = get_posts([
            'post_type' => 'artwork',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'gallery',
                    'field' => 'term_id',
                    'terms' => [$term_id],
                ],
            ],
        ]);

        return is_array($artworks) ? $artworks : [];
    }

}
