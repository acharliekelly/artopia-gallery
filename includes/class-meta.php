<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Meta {
  public function register(): void {
    $this->register_artwork_meta();
  }

  private function register_artwork_meta(): void {
    // core artwork meta
    $defaults = Artwork_Data::defaults();

    register_post_meta('artwork', '_artopia_artist_id', [
      'type' => 'integer',
      'single' => true,
      'default' => $defaults['artist_id'],
      'sanitize_callback' => [$this, 'sanitize_artist_id'],
      'show_in_rest' => true,
      'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);

    register_post_meta('artwork', '_artopia_filename', [
      'type' => 'string',
      'single' => true,
      'default' => $defaults['filename'],
      'sanitize_callback' => [Artwork_Data::class, 'normalize_filename'],
      'show_in_rest' => true,
      'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);

    register_post_meta('artwork', '_artopia_medium', [
      'type' => 'string',
      'single' => true,
      'default' => $defaults['medium'],
      'sanitize_callback' => [Artwork_Data::class, 'normalize_medium'],
      'show_in_rest' => true,
      'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);

    register_post_meta('artwork', '_artopia_year', [
      'type' => 'integer',
      'single' => true,
      'default' => $defaults['year'],
      'sanitize_callback' => [Artwork_Data::class, 'normalize_year'],
      'show_in_rest' => true,
      'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);

    register_post_meta('artwork', '_artopia_dimensions', [
      'type' => 'string',
      'single' => true,
      'default' => $defaults['dimensions'],
      'sanitize_callback' => [Artwork_Data::class, 'normalize_dimensions'],
      'show_in_rest' => true,
      'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);

    register_post_meta('artwork', '_artopia_price', [
      'type' => 'string',
      'single' => true,
      'default' => $defaults['price'],
      'sanitize_callback' => [Artwork_Data::class, 'normalize_price'],
      'show_in_rest' => true,
      'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);

    register_post_meta('artwork', '_artopia_status', [
      'type' => 'string',
      'single' => true,
      'default' => $defaults['status'],
      'sanitize_callback' => [Artwork_Data::class, 'normalize_status'],
      'show_in_rest' => true,
      'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);

    register_post_meta('artwork', '_artopia_import_key', [
      'type' => 'string',
      'single' => true,
      'default' => '',
      'sanitize_callback' => 'sanitize_text_field',
      'show_in_rest' => true,
      'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);
  }

  public function can_edit_post_meta(): bool {
    return current_user_can('edit_posts');
  }

  public function sanitize_artist_id($value): int {
    $artist_id = Artwork_Data::normalize_artist_id($value);

    if (!$artist_id) {
      return 0;
    }

    $artist = get_post($artist_id);

    if (!$artist || $artist->post_type !== 'artist') {
      return 0;
    }

    return $artist_id;
  }
}
