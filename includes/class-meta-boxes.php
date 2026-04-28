<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Meta_Boxes {

  public function run(): void {
    add_action( 'add_meta_boxes', [$this, 'register_artwork_meta_box']);
    add_action( 'save_post', [$this, 'save_artwork_details']);
  }

  public function register_artwork_meta_box(): void {
    add_meta_box(
      'artopia_artwork_details',
      __('Artwork Details', 'artopia-gallery'),
      [$this, 'render_artwork_details_meta_box'],
      'artwork',
      'normal',
      'default'
    );
  }

  public function render_artwork_details_meta_box(\WP_Post $post): void {
    wp_nonce_field('artopia_save_artwork_details', 'artopia_artwork_details_nonce');

    $artist_id  = (int) get_post_meta($post->ID, '_artopia_artist_id', true);
    $medium     = get_post_meta( $post->ID, '_artopia_medium', true );
    $year       = get_post_meta( $post->ID, '_artopia_year', true );
    $dimensions = get_post_meta( $post->ID, '_artopia_dimensions', true );
    $price      = get_post_meta( $post->ID, '_artopia_price', true );
    $status     = get_post_meta( $post->ID, '_artopia_status', true);

    if (empty($status)) {
      $status = 'available';
    }

    $artists = get_posts([
        'post_type'         => 'artist',
        'post_status'       => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page'    => -1,
        'orderby'           => 'title',
        'order'             => 'ASC',
    ]);

    echo '<p>';
    echo '<label for="artopia_artist_id"><strong>' . esc_html__('Artist', 'artopia-gallery') . '</strong></label><br>';
    echo '<select id="artopia_artist_id" name="artopia_artist_id">';
    echo '<option value="0">' . esc_html__('Select an artist', 'artopia-gallery') . '</option>';

    foreach ($artists as $artist) {
        echo '<option value="' . esc_attr((string) $artist->ID) . '"' . selected($artist_id, $artist->ID, false) . '>';
        echo esc_html($artist->post_title ?: sprintf(__('Artist #%d', 'artopia-gallery'), $artist->ID));
        echo '</option>';
    }

    echo '</select>';
    echo '</p>';

    echo '<p>';
        echo '<label for="artopia_medium"><strong>' . esc_html__('Medium', 'artopia-gallery') . '</strong></label><br>';
        echo '<input type="text" id="artopia_medium" name="artopia_medium" value="' . esc_attr($medium) . '" class="regular-text">';
        echo '</p>';

        echo '<p>';
        echo '<label for="artopia_year"><strong>' . esc_html__('Year', 'artopia-gallery') . '</strong></label><br>';
        echo '<input type="number" id="artopia_year" name="artopia_year" value="' . esc_attr($year) . '" class="small-text" min="0" max="9999" step="1">';
        echo '</p>';

        echo '<p>';
        echo '<label for="artopia_dimensions"><strong>' . esc_html__('Dimensions', 'artopia-gallery') . '</strong></label><br>';
        echo '<input type="text" id="artopia_dimensions" name="artopia_dimensions" value="' . esc_attr($dimensions) . '" class="regular-text" placeholder="e.g. 24 x 36 in">';
        echo '</p>';

        echo '<p>';
        echo '<label for="artopia_price"><strong>' . esc_html__('Price', 'artopia-gallery') . '</strong></label><br>';
        echo '<input type="text" id="artopia_price" name="artopia_price" value="' . esc_attr($price) . '" class="regular-text" placeholder="e.g. 1200.00">';
        echo '</p>';

        echo '<p>';
        echo '<label for="artopia_status"><strong>' . esc_html__('Status', 'artopia-gallery') . '</strong></label><br>';
        echo '<select id="artopia_status" name="artopia_status">';

        foreach (Helpers::artwork_statuses() as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($status, $value, false) . '>';
            echo esc_html($label);
            echo '</option>';
        }

        echo '</select>';
        echo '</p>';
    }

  public function save_artwork_details(int $post_id): void {
    if (!isset($_POST['artopia_artwork_details_nonce'])) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['artopia_artwork_details_nonce']));

    if (!wp_verify_nonce($nonce, 'artopia_save_artwork_details')) {
        return;
    }

    if (wp_is_post_autosave($post_id)) {
        return;
    }

    if ($parent_id = wp_is_post_revision($post_id)) {
        $post_id = $parent_id;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $raw_data = [
        'artist_id' => isset($_POST['artopia_artist_id'])
            ? wp_unslash($_POST['artopia_artist_id'])
            : 0,
        'medium' => isset($_POST['artopia_medium'])
            ? wp_unslash($_POST['artopia_medium'])
            : '',
        'year' => isset($_POST['artopia_year'])
            ? wp_unslash($_POST['artopia_year'])
            : '',
        'dimensions' => isset($_POST['artopia_dimensions'])
            ? wp_unslash($_POST['artopia_dimensions'])
            : '',
        'price' => isset($_POST['artopia_price'])
            ? wp_unslash($_POST['artopia_price'])
            : '',
        'status' => isset($_POST['artopia_status'])
            ? wp_unslash($_POST['artopia_status'])
            : '',
    ];

    $data = Artwork_Data::normalize($raw_data);

    $artist_id = 0;

    if ($data['artist_id'] > 0) {
        $artist = get_post($data['artist_id']);

        if ($artist && $artist->post_type === 'artist') {
            $artist_id = $data['artist_id'];
        }
    }

    update_post_meta($post_id, '_artopia_artist_id', $artist_id);
    update_post_meta($post_id, '_artopia_medium', $data['medium']);
    update_post_meta($post_id, '_artopia_year', $data['year']);
    update_post_meta($post_id, '_artopia_dimensions', $data['dimensions']);
    update_post_meta($post_id, '_artopia_price', $data['price']);
    update_post_meta($post_id, '_artopia_status', $data['status']);
  }

}