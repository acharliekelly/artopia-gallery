<?php
if (!defined('ABSPATH')) {
    exit;
}

$term = get_queried_object();
$artist_id = 0;

if ($term instanceof \WP_Term) {
    $gallery_terms = new \Artopia_Gallery\Gallery_Terms();
    $artist_id = $gallery_terms->get_artist_id_for_term((int) $term->term_id);
}

\Artopia_Gallery\Shortcodes::enqueue_public_assets();

get_header();
?>

<main class="artopia-gallery-taxonomy-page">
    <?php
    if ($term instanceof \WP_Term) {
        echo (new \Artopia_Gallery\Shortcodes())->render_gallery([
            'gallery' => $term->slug,
            'artist_id' => $artist_id,
        ]);
    } else {
        echo '<div class="artopia-gallery-empty">' .
            esc_html__('Gallery not found.', 'artopia-gallery') .
            '</div>';
    }
    ?>
</main>

<?php
get_footer();
